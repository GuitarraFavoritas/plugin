<?php
// includes/templates/admin-disponibilidad-row.php
/**
 * Plantilla para renderizar una fila de bloque de disponibilidad.
 * Espera que las variables $index, $bloque, $dias_semana, $sedes, $programas, $rangos estén definidas.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Asegurarse de que las variables esperadas existan (aunque sea vacías) para evitar errores
$index ??= ''; // PHP 7.4+ null coalesce assignment
$bloque ??= [];
$dias_semana ??= [];
$sedes ??= [];
$programas ??= [];
$rangos ??= [];
// Valores por defecto o guardados
$dia_seleccionado = isset( $bloque['dia'] ) ? $bloque['dia'] : '';
$hora_inicio = isset( $bloque['hora_inicio'] ) ? $bloque['hora_inicio'] : '';
$hora_fin = isset( $bloque['hora_fin'] ) ? $bloque['hora_fin'] : '';
$sedes_seleccionadas = isset( $bloque['sedes'] ) && is_array( $bloque['sedes'] ) ? $bloque['sedes'] : array();
$programas_seleccionados = isset( $bloque['programas'] ) && is_array( $bloque['programas'] ) ? $bloque['programas'] : array();
$rangos_seleccionados = isset( $bloque['rangos'] ) && is_array( $bloque['rangos'] ) ? $bloque['rangos'] : array();
?>
<div class="msh-bloque-row">
    <span class="msh-drag-handle dashicons dashicons-menu" title="<?php esc_attr_e('Arrastrar para reordenar', 'music-schedule-manager'); ?>"></span> <?php // Opcional: Handle para arrastrar ?>
    <div class="msh-bloque-field-group msh-field-dia">
        <label for="msh_disponibilidad_<?php echo esc_attr( $index ); ?>_dia"><?php esc_html_e( 'Día:', 'music-schedule-manager' ); ?></label>
        <select name="msh_disponibilidad[<?php echo esc_attr( $index ); ?>][dia]" id="msh_disponibilidad_<?php echo esc_attr( $index ); ?>_dia">
            <option value=""><?php esc_html_e( 'Seleccionar', 'music-schedule-manager' ); ?></option>
            <?php foreach ( $dias_semana as $key => $label ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $dia_seleccionado, $key ); ?>><?php echo esc_html( $label ); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="msh-bloque-field-group msh-field-hora-inicio">
        <label for="msh_disponibilidad_<?php echo esc_attr( $index ); ?>_hora_inicio"><?php esc_html_e( 'Inicio:', 'music-schedule-manager' ); ?></label>
        <input type="time" name="msh_disponibilidad[<?php echo esc_attr( $index ); ?>][hora_inicio]" id="msh_disponibilidad_<?php echo esc_attr( $index ); ?>_hora_inicio" value="<?php echo esc_attr( $hora_inicio ); ?>" >
    </div>
    <div class="msh-bloque-field-group msh-field-hora-fin">
        <label for="msh_disponibilidad_<?php echo esc_attr( $index ); ?>_hora_fin"><?php esc_html_e( 'Fin:', 'music-schedule-manager' ); ?></label>
        <input type="time" name="msh_disponibilidad[<?php echo esc_attr( $index ); ?>][hora_fin]" id="msh_disponibilidad_<?php echo esc_attr( $index ); ?>_hora_fin" value="<?php echo esc_attr( $hora_fin ); ?>" >
    </div>
    <div class="msh-bloque-field-group msh-field-sedes">
        <label for="msh_disponibilidad_<?php echo esc_attr( $index ); ?>_sedes"><?php esc_html_e( 'Sedes:', 'music-schedule-manager' ); ?></label> <?php // Etiqueta acortada ?>
        <select multiple name="msh_disponibilidad[<?php echo esc_attr( $index ); ?>][sedes][]" id="msh_disponibilidad_<?php echo esc_attr( $index ); ?>_sedes">
            <?php if ( $sedes ) : ?>
                <?php foreach ( $sedes as $sede ) : ?>
                    <option value="<?php echo esc_attr( $sede->ID ); ?>" <?php selected( in_array( $sede->ID, $sedes_seleccionadas ) ); ?>>
                        <?php echo esc_html( $sede->post_title ); ?>
                    </option>
                <?php endforeach; ?>
            <?php else: ?>
                 <option value="" disabled><?php esc_html_e( 'No hay sedes', 'music-schedule-manager' ); ?></option>
            <?php endif; ?>
        </select>
    </div>
    <div class="msh-bloque-field-group msh-field-programas">
         <label for="msh_disponibilidad_<?php echo esc_attr( $index ); ?>_programas"><?php esc_html_e( 'Programas:', 'music-schedule-manager' ); ?></label> <?php // Etiqueta acortada ?>
        <select multiple name="msh_disponibilidad[<?php echo esc_attr( $index ); ?>][programas][]" id="msh_disponibilidad_<?php echo esc_attr( $index ); ?>_programas">
            <?php if ( $programas ) : ?>
                <?php foreach ( $programas as $programa ) : ?>
                    <option value="<?php echo esc_attr( $programa->ID ); ?>" <?php selected( in_array( $programa->ID, $programas_seleccionados ) ); ?>>
                        <?php echo esc_html( $programa->post_title ); ?>
                    </option>
                <?php endforeach; ?>
             <?php else: ?>
                 <option value="" disabled><?php esc_html_e( 'No hay programas', 'music-schedule-manager' ); ?></option>
            <?php endif; ?>
        </select>
    </div>
    <div class="msh-bloque-field-group msh-field-rangos">
        <label for="msh_disponibilidad_<?php echo esc_attr( $index ); ?>_rangos"><?php esc_html_e( 'Edades:', 'music-schedule-manager' ); ?></label> <?php // Etiqueta acortada ?>
        <select multiple name="msh_disponibilidad[<?php echo esc_attr( $index ); ?>][rangos][]" id="msh_disponibilidad_<?php echo esc_attr( $index ); ?>_rangos">
             <?php if ( $rangos ) : ?>
                <?php foreach ( $rangos as $rango ) : ?>
                    <option value="<?php echo esc_attr( $rango->ID ); ?>" <?php selected( in_array( $rango->ID, $rangos_seleccionados ) ); ?>>
                        <?php echo esc_html( $rango->post_title ); ?>
                    </option>
                <?php endforeach; ?>
            <?php else: ?>
                 <option value="" disabled><?php esc_html_e( 'No hay rangos', 'music-schedule-manager' ); ?></option>
            <?php endif; ?>
        </select>
    </div>
    <a href="#" class="msh-remove-bloque" title="<?php esc_attr_e( 'Eliminar este bloque', 'music-schedule-manager' ); ?>"><span class="dashicons dashicons-trash"></span></a>
</div>