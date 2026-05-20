<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\LabConfigRepository;

final class LabConfigService
{
    public function __construct(
        private LabConfigRepository $configRepo,
        private AuditoriaService $auditoria,
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function getDatosLaboratorio(): array
    {
        $config = $this->configRepo->getAll();

        $config['firmante'] = [
            'apellido'   => (string) ($config['firmante_apellido'] ?? ''),
            'nombres'    => (string) ($config['firmante_nombres'] ?? ''),
            'matricula'  => (string) ($config['firmante_matricula'] ?? ''),
            'titulo'     => (string) ($config['firmante_titulo'] ?? ''),
            'firma_path' => $config['firmante_firma_path'] ?? null,
        ];

        return $config;
    }

    /**
     * @param array<string,?string> $kvs
     */
    public function actualizarDatos(array $kvs, ?int $usuarioId): void
    {
        $valoresAnteriores = [];
        foreach (array_keys($kvs) as $clave) {
            $valoresAnteriores[$clave] = $this->configRepo->get($clave);
        }

        foreach ($kvs as $clave => $valor) {
            $this->configRepo->set($clave, $valor);
        }

        $this->auditoria->log(
            usuarioId: $usuarioId,
            accion: 'actualizar',
            tablaAfectada: 'lab_config',
            registroId: null,
            valorAnterior: $valoresAnteriores,
            valorNuevo: $kvs,
            contexto: 'Edicion masiva de configuracion del laboratorio',
        );
    }

    /**
     * Mueve el archivo subido a storage/logos/ y guarda el path en config.
     * Devuelve el path relativo guardado.
     *
     * @param array{tmp_name:string,name:string,error:int} $file Estructura de $_FILES
     */
    public function subirLogo(array $file, ?int $usuarioId): string
    {
        return $this->subirImagen($file, 'logos', 'logo', 'laboratorio_logo_path', $usuarioId);
    }

    /**
     * Sube la firma escaneada del bioquimico responsable.
     *
     * @param array{tmp_name:string,name:string,error:int} $file
     */
    public function subirFirma(array $file, ?int $usuarioId): string
    {
        return $this->subirImagen($file, 'firmas', 'firma', 'firmante_firma_path', $usuarioId);
    }

    /**
     * @param array{tmp_name:string,name:string,error:int} $file
     */
    private function subirImagen(array $file, string $subdir, string $prefix, string $configKey, ?int $usuarioId): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Error de subida (codigo ' . $file['error'] . ')');
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg'], true)) {
            throw new \RuntimeException('Solo se aceptan imagenes PNG/JPG');
        }

        $destDir = __DIR__ . '/../../storage/' . $subdir;
        if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) {
            throw new \RuntimeException("No se pudo crear directorio: $destDir");
        }
        $nombre = $prefix . '_' . date('Ymd_His') . '.' . $ext;
        $destFull = $destDir . '/' . $nombre;
        if (!move_uploaded_file($file['tmp_name'], $destFull)) {
            throw new \RuntimeException('No se pudo mover el archivo subido');
        }

        $relativo = $subdir . '/' . $nombre;
        $this->actualizarDatos([$configKey => $relativo], $usuarioId);
        return $relativo;
    }
}
