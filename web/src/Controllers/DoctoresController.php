<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Repositories/DoctoresRepository.php';

final class DoctoresController
{
    /** @var PDO */
    private $pdo;
    /** @var array|null */
    private $user;

    public function __construct(PDO $pdo, ?array $user)
    {
        $this->pdo = $pdo;
        $this->user = $user;
    }

    public function index(): void
    {
        $repo = new DoctoresRepository($this->pdo);
        $extDoc = $repo->hasExtendedColumns();
        $rows = $repo->listForIndex($extDoc);

        $body = $this->renderView('doctores/index', [
            'extDoc' => $extDoc,
            'rows' => $rows,
        ]);
        layout_render('Doctores', $body, $this->user);
    }

    public function form(): void
    {
        $repo = new DoctoresRepository($this->pdo);
        $ext = $repo->hasExtendedColumns();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $row = [
            'id' => 0,
            'nombre' => '',
            'medicoconvenio' => 0,
            'bloquearmisconsultas' => 0,
            'activo' => 1,
            'notas' => '',
        ];
        if ($ext) {
            $row = array_merge($row, [
                'especialidad' => '', 'matricula' => '', 'telefono' => '',
                'domicilio' => '', 'localidad' => '', 'consultorio' => '',
            ]);
        }

        if ($id > 0) {
            $loaded = $repo->findById($id);
            if (!$loaded) {
                flash_set('Profesional no encontrado.');
                header('Location: /doctores.php');
                exit;
            }
            $row = array_merge($row, $loaded);
        }

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            $nombre = trim((string) ($_POST['nombre'] ?? ''));
            $medicoconvenio = isset($_POST['medicoconvenio']) ? 1 : 0;
            $bloquearmisconsultas = isset($_POST['bloquearmisconsultas']) ? 1 : 0;
            $activo = isset($_POST['activo']) ? 1 : 0;
            $notas = trim((string) ($_POST['notas'] ?? ''));

            $ex = [];
            if ($ext) {
                $ex = [
                    'especialidad' => trim((string) ($_POST['especialidad'] ?? '')),
                    'matricula' => trim((string) ($_POST['matricula'] ?? '')),
                    'telefono' => trim((string) ($_POST['telefono'] ?? '')),
                    'domicilio' => trim((string) ($_POST['domicilio'] ?? '')),
                    'localidad' => trim((string) ($_POST['localidad'] ?? '')),
                    'consultorio' => trim((string) ($_POST['consultorio'] ?? '')),
                ];
            }

            if ($nombre === '') {
                $error = 'El nombre es obligatorio.';
            } else {
                if ($ext) {
                    if ($id > 0) {
                        $repo->updateExtended(
                            $id,
                            $nombre,
                            $medicoconvenio,
                            $bloquearmisconsultas,
                            $activo,
                            $notas,
                            $ex['especialidad'],
                            $ex['matricula'],
                            $ex['telefono'],
                            $ex['domicilio'],
                            $ex['localidad'],
                            $ex['consultorio']
                        );
                    } else {
                        $repo->insertExtended(
                            $nombre,
                            $medicoconvenio,
                            $bloquearmisconsultas,
                            $activo,
                            $notas,
                            $ex['especialidad'],
                            $ex['matricula'],
                            $ex['telefono'],
                            $ex['domicilio'],
                            $ex['localidad'],
                            $ex['consultorio']
                        );
                    }
                } else {
                    if ($id > 0) {
                        $repo->updateBase($id, $nombre, $medicoconvenio, $bloquearmisconsultas, $activo, $notas);
                    } else {
                        $repo->insertBase($nombre, $medicoconvenio, $bloquearmisconsultas, $activo, $notas);
                    }
                }
                flash_set($id > 0 ? 'Profesional actualizado.' : 'Profesional creado.');
                header('Location: /doctores.php');
                exit;
            }

            $row = array_merge($row, [
                'id' => $id, 'nombre' => $nombre, 'medicoconvenio' => $medicoconvenio,
                'bloquearmisconsultas' => $bloquearmisconsultas, 'activo' => $activo, 'notas' => $notas,
            ]);
            if ($ext && isset($ex)) {
                $row = array_merge($row, $ex);
            }
        }

        $titulo = $row['id'] ? 'Editar profesional' : 'Nuevo profesional';
        $body = $this->renderView('doctores/form', [
            'ext' => $ext,
            'row' => $row,
            'error' => $error,
            'titulo' => $titulo,
        ]);
        layout_render($titulo, $body, $this->user);
    }

    public function deletePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /doctores.php');
            exit;
        }
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            header('Location: /doctores.php');
            exit;
        }
        $repo = new DoctoresRepository($this->pdo);
        $repo->deleteById($id);
        flash_set('Profesional eliminado.');
        header('Location: /doctores.php');
        exit;
    }

    private function renderView(string $view, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';
        return (string) ob_get_clean();
    }
}

