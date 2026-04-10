<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/catalogos.php';
require_once dirname(__DIR__) . '/Catalog/CatalogRegistry.php';
require_once dirname(__DIR__) . '/Repositories/CatalogosListaRepository.php';

final class CatalogosController
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
        $repo = new CatalogosListaRepository($this->pdo);
        $items = [];
        foreach (CatalogRegistry::definitions() as $tabla => $def) {
            $ok = $repo->tablaExiste($tabla);
            $items[] = [
                'tabla' => $tabla,
                'titulo' => $def['titulo'],
                'ok' => $ok,
                'orden' => $def['orden'],
                'campos_count' => count($def['campos']),
                'rows_count' => $ok ? $repo->contarRegistros($tabla) : null,
            ];
        }
        $body = $this->renderView('catalogos/index', ['items' => $items]);
        layout_render('Tablas auxiliares', $body, $this->user);
    }

    public function listar(string $tabla): void
    {
        $def = CatalogRegistry::get($tabla);
        if ($def === null) {
            flash_set('Catálogo no permitido.');
            header('Location: /catalogos.php');
            exit;
        }
        $repo = new CatalogosListaRepository($this->pdo);
        if (!$repo->tablaExiste($tabla)) {
            flash_set('La tabla no existe en esta base.');
            header('Location: /catalogos.php');
            exit;
        }
        $rows = $repo->listar($tabla, $def['orden']);
        $body = $this->renderView('catalogos/lista', [
            'tabla' => $tabla,
            'titulo' => $def['titulo'],
            'rows' => $rows,
            'campos' => $def['campos'],
        ]);
        layout_render($def['titulo'], $body, $this->user);
    }

    public function form(): void
    {
        $tabla = trim((string) ($_GET['tabla'] ?? ''));
        $def = CatalogRegistry::get($tabla);
        if ($def === null) {
            flash_set('Catálogo no permitido.');
            header('Location: /catalogos.php');
            exit;
        }
        $repo = new CatalogosListaRepository($this->pdo);
        if (!$repo->tablaExiste($tabla)) {
            flash_set('La tabla no existe en esta base.');
            header('Location: /catalogos.php');
            exit;
        }

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $row = ['id' => $id];
        foreach ($def['campos'] as $col => $meta) {
            $row[$col] = $meta['tipo'] === 'int' || $meta['tipo'] === 'fk' ? null : '';
            if ($meta['tipo'] === 'decimal') {
                $row[$col] = null;
            }
        }
        if ($id > 0) {
            $loaded = $repo->findById($tabla, $id);
            if (!$loaded) {
                flash_set('Ítem no encontrado.');
                header('Location: /catalogos.php?a=list&tabla=' . rawurlencode($tabla));
                exit;
            }
            $row = $loaded;
        }

        $error = '';
        $fkOptions = $this->fkOptionsFor($def['campos']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            $parsed = $this->parseCampos($def['campos'], $_POST, $error);
            if ($error === '') {
                if ($id > 0) {
                    $repo->update($tabla, $id, $parsed);
                    flash_set('Registro actualizado.');
                } else {
                    $repo->insert($tabla, $parsed);
                    flash_set('Registro creado.');
                }
                header('Location: /catalogos.php?a=list&tabla=' . rawurlencode($tabla));
                exit;
            }
            $row = array_merge($row, $this->rowFromPost($def['campos'], $_POST));
        }

        $body = $this->renderView('catalogos/form', [
            'tabla' => $tabla,
            'titulo' => $def['titulo'],
            'row' => $row,
            'campos' => $def['campos'],
            'fkOptions' => $fkOptions,
            'error' => $error,
        ]);
        $sub = ($row['id'] ?? 0) ? 'Editar' : 'Nuevo';
        layout_render($sub . ' · ' . $def['titulo'], $body, $this->user);
    }

    public function deletePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /catalogos.php');
            exit;
        }
        $tabla = trim((string) ($_POST['tabla'] ?? ''));
        $def = CatalogRegistry::get($tabla);
        if ($def === null) {
            header('Location: /catalogos.php');
            exit;
        }
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            header('Location: /catalogos.php?a=list&tabla=' . rawurlencode($tabla));
            exit;
        }
        $repo = new CatalogosListaRepository($this->pdo);
        try {
            $repo->deleteById($tabla, $id);
            flash_set('Registro eliminado.');
        } catch (Throwable $e) {
            flash_set('No se pudo eliminar (puede estar en uso en pacientes u órdenes).');
        }
        header('Location: /catalogos.php?a=list&tabla=' . rawurlencode($tabla));
        exit;
    }

    /**
     * @param array<string, array{tipo:string, label:string, requerido?:bool, ref?:string, max?:int}> $campos
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function parseCampos(array $campos, array $post, string &$error): array
    {
        $out = [];
        foreach ($campos as $col => $meta) {
            $raw = trim((string) ($post[$col] ?? ''));
            if ($meta['tipo'] === 'text') {
                if (!empty($meta['requerido']) && $raw === '') {
                    $error = 'Completá «' . $meta['label'] . '».';

                    return [];
                }
                $max = (int) ($meta['max'] ?? 255);
                if ($raw !== '' && $max > 0 && mb_strlen($raw) > $max) {
                    $error = '«' . $meta['label'] . '» supera el máximo de ' . $max . ' caracteres.';

                    return [];
                }
                $out[$col] = $raw === '' ? null : $raw;
            } elseif ($meta['tipo'] === 'int') {
                if ($raw === '') {
                    $out[$col] = null;
                } else {
                    if (!preg_match('/^-?\d+$/', $raw)) {
                        $error = '«' . $meta['label'] . '» debe ser un número entero.';

                        return [];
                    }
                    $out[$col] = (int) $raw;
                }
            } elseif ($meta['tipo'] === 'decimal') {
                if ($raw === '') {
                    $out[$col] = null;
                } else {
                    $raw = str_replace(',', '.', $raw);
                    if (!is_numeric($raw)) {
                        $error = '«' . $meta['label'] . '» debe ser numérico.';

                        return [];
                    }
                    $out[$col] = (float) $raw;
                }
            } elseif ($meta['tipo'] === 'fk') {
                if ($raw === '') {
                    if (!empty($meta['requerido'])) {
                        $error = 'Elegí «' . $meta['label'] . '».';

                        return [];
                    }
                    $out[$col] = null;
                } else {
                    $out[$col] = (int) $raw;
                }
            }
        }

        return $out;
    }

    /**
     * @param array<string, array{tipo:string, label:string, requerido?:bool, ref?:string, max?:int}> $campos
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function rowFromPost(array $campos, array $post): array
    {
        $row = [];
        foreach ($campos as $col => $meta) {
            $v = $post[$col] ?? '';
            $row[$col] = is_string($v) ? $v : (string) $v;
        }

        return $row;
    }

    /**
     * @param array<string, array{tipo:string, ref?:string}> $campos
     * @return array<string, list<array{id:int|string,nombre:?string}>>
     */
    private function fkOptionsFor(array $campos): array
    {
        $opts = [];
        foreach ($campos as $meta) {
            if (($meta['tipo'] ?? '') === 'fk' && !empty($meta['ref'])) {
                $ref = (string) $meta['ref'];
                if (!isset($opts[$ref])) {
                    $opts[$ref] = catalogo_lista($this->pdo, $ref);
                }
            }
        }

        return $opts;
    }

    private function renderView(string $view, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';

        return (string) ob_get_clean();
    }
}
