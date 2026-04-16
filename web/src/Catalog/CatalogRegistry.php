<?php

declare(strict_types=1);

/**
 * Catálogos tipo "Lista *" permitidos para ABM web.
 * Solo tablas listadas aquí son editables (evita abuso de rutas).
 */
final class CatalogRegistry
{
    /**
     * @return array<string, array{titulo:string, orden:'prioridad_id'|'nombre', campos:array<string, array{tipo:string, label:string, requerido?:bool, ref?:string, max?:int}>}>
     */
    public static function definitions(): array
    {
        return [
            'lista_coberturas' => [
                'titulo' => 'Obras sociales / coberturas',
                'orden' => 'prioridad_id',
                'campos' => [
                    'prioridad' => ['tipo' => 'int', 'label' => 'Prioridad', 'requerido' => false],
                    'nombre' => ['tipo' => 'text', 'label' => 'Nombre', 'requerido' => true, 'max' => 255],
                    'porcentaje_cobertura' => ['tipo' => 'decimal', 'label' => '% cobertura', 'requerido' => false],
                    'plancober' => ['tipo' => 'text', 'label' => 'Plan c/ cob.', 'requerido' => false, 'max' => 255],
                ],
            ],
            'lista_planes' => [
                'titulo' => 'Planes (por cobertura)',
                'orden' => 'nombre',
                'campos' => [
                    'id_cobertura' => ['tipo' => 'fk', 'label' => 'Cobertura', 'ref' => 'lista_coberturas', 'requerido' => false],
                    'nombre' => ['tipo' => 'text', 'label' => 'Nombre del plan', 'requerido' => true, 'max' => 255],
                ],
            ],
            'lista_pais' => self::simplePrioridadNombre('Países', 100),
            'lista_provincia' => self::simplePrioridadNombre('Provincias', 100),
            'lista_ciudad' => self::simplePrioridadNombre('Ciudades / localidades', 100),
            'lista_tipo_documento' => self::simplePrioridadNombre('Tipos de documento', 255),
            'lista_ocupacion' => self::simplePrioridadNombre('Ocupaciones', 255),
            'lista_estado_civil' => self::simplePrioridadNombre('Estado civil', 255),
            'lista_etnia' => self::simplePrioridadNombre('Etnia', 255),
            'lista_relacion_paciente' => self::simplePrioridadNombre('Relación con el paciente', 255),
            'lista_estatus_pais' => self::simplePrioridadNombre('Estatus en el país', 255),
            'lista_sexo' => self::simplePrioridadNombre('Sexo registral', 100),
            'lista_grupo_sanguineo' => self::simplePrioridadNombre('Grupo sanguíneo', 20),
            'lista_factor_sanguineo' => self::simplePrioridadNombre('Factor RH', 30),
            'lista_identidad_genero' => self::simplePrioridadNombre('Identidad de género', 150),
            'lista_orientacion_sex' => self::simplePrioridadNombre('Orientación sexual', 150),
            'lista_motivos_consulta' => self::simplePrioridadNombre('Motivos de consulta (agenda)', 255),
            'lista_primera_vez' => self::simplePrioridadNombre('Tipo de atención inicial (agenda)', 120),
            'lista_practicas' => self::simplePrioridadNombre('Prácticas / estudios (órdenes)', 255),
            'lista_derivaciones' => self::simplePrioridadNombre('Derivaciones / derivadores (órdenes)', 255),
            'lista_sucursales' => self::simplePrioridadNombre('Sucursales (órdenes)', 100),
            'lista_odontograma_codigos' => [
                'titulo' => 'Códigos odontograma (leyenda clínica)',
                'orden' => 'prioridad_id',
                'campos' => [
                    'prioridad' => ['tipo' => 'int', 'label' => 'Prioridad', 'requerido' => false],
                    'codigo' => ['tipo' => 'text', 'label' => 'Símbolo / abrev.', 'requerido' => false, 'max' => 12],
                    'nombre' => ['tipo' => 'text', 'label' => 'Descripción', 'requerido' => true, 'max' => 255],
                    'color_hex' => ['tipo' => 'text', 'label' => 'Color mapa (#RRGGBB)', 'requerido' => false, 'max' => 7],
                    'mapa_overlay' => ['tipo' => 'text', 'label' => 'Marca pieza (mapa): vacío=caras; pieza_diagonal|pieza_x|pieza_circulo|pieza_relleno', 'requerido' => false, 'max' => 24],
                ],
            ],
        ];
    }

    public static function titulo(string $tabla): string
    {
        $def = self::definitions();
        if (!isset($def[$tabla])) {
            return $tabla;
        }

        return $def[$tabla]['titulo'];
    }

    /**
     * @return array{titulo:string, orden:string, campos:array}|null
     */
    public static function get(string $tabla): ?array
    {
        $def = self::definitions();

        return $def[$tabla] ?? null;
    }

    /**
     * @return array<string, array{titulo:string, orden:string, campos:array}>
     */
    private static function simplePrioridadNombre(string $titulo, int $maxNombre): array
    {
        return [
            'titulo' => $titulo,
            'orden' => 'prioridad_id',
            'campos' => [
                'prioridad' => ['tipo' => 'int', 'label' => 'Prioridad', 'requerido' => false],
                'nombre' => ['tipo' => 'text', 'label' => 'Nombre', 'requerido' => true, 'max' => $maxNombre],
            ],
        ];
    }
}
