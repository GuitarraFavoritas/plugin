<?php
// includes/meta-boxes/maestro-disponibilidad.php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registra la Meta Box para Disponibilidad General del Maestro (UI con tabla y modal).
 * (Se muestra en la pantalla de edición del CPT 'msh_maestro')
 */
function msh_add_disponibilidad_meta_box() {
    add_meta_box(
        'msh_maestro_disponibilidad',                    // ID único
        __( 'Disponibilidad General (Horarios Vacíos)', 'music-schedule-manager' ), // Título actualizado
        'msh_maestro_disponibilidad_metabox_render',     // Callback HTML
        'msh_maestro',                                   // CPT
        'normal',                                        // Contexto
        'default'                                        // Prioridad
    );
}
add_action( 'add_meta_boxes_msh_maestro', 'msh_add_disponibilidad_meta_box' );

/**
 * Renderiza el contenido de la Meta Box de Disponibilidad General (Tabla + Controles).
 * La tabla se rellena vía JavaScript (admin-script.js) usando datos localizados.
 *
 * @param WP_Post $post El objeto del post actual (Maestro).
 */
function msh_maestro_disponibilidad_metabox_render( $post ) {
    // Nonce para acciones generales de esta meta box (cargar form modal)
    wp_nonce_field( 'msh_manage_disponibilidad_action', 'msh_manage_disponibilidad_nonce' );
    // Nonce ESPECÍFICO para la acción de guardar TODA la disponibilidad (usado por el botón Guardar)
    wp_nonce_field( 'msh_save_disponibilidad_action', 'msh_save_disponibilidad_nonce' );

    ?>
    <div id="msh-disponibilidad-manager-container"> <?php // Contenedor principal para JS ?>
        <p><?php esc_html_e( 'Define los bloques generales de tiempo donde el maestro puede estar disponible. Los bloques duplicados (mismo día y hora de inicio) serán ignorados al guardar.', 'music-schedule-manager' ); ?></p>

        <div class="msh-availability-table-wrapper">
            <table class="wp-list-table widefat fixed striped" id="msh-availability-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Día', 'music-schedule-manager'); ?></th>
                        <th><?php esc_html_e('Horario', 'music-schedule-manager'); ?></th>
                        <th><?php esc_html_e('Sedes Admisibles', 'music-schedule-manager'); ?></th>
                        <th><?php esc_html_e('Programas Admisibles', 'music-schedule-manager'); ?></th>
                        <th><?php esc_html_e('Edades Admisibles', 'music-schedule-manager'); ?></th>
                        <th style="width: 100px;"><?php esc_html_e('Acciones', 'music-schedule-manager'); ?></th>
                    </tr>
                </thead>
                <tbody id="msh-availability-list"> <?php // ID para JS (admin-script.js) ?>
                    <?php // Las filas se cargarán aquí mediante JavaScript ?>
                    <tr id="msh-availability-loading-row">
                        <td colspan="6"><?php esc_html_e( 'Cargando disponibilidad...', 'music-schedule-manager'); ?></td>
                    </tr>
                     <tr id="msh-no-availability-row" style="display: none;">
                         <td colspan="6"><?php esc_html_e('No se han definido bloques de disponibilidad.', 'music-schedule-manager'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="msh-availability-actions">
            <?php // Botones para JS (admin-script.js) ?>
            <button type="button" id="msh-add-availability-btn" class="button button-secondary">
                <span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e('Añadir Bloque', 'music-schedule-manager'); ?>
            </button>
            <button type="button" id="msh-save-availability-changes-btn" class="button button-primary" disabled>
                <?php esc_html_e('Guardar Cambios Disponibilidad', 'music-schedule-manager'); ?>
            </button>
            <span id="msh-availability-spinner" class="spinner" style="float: none; vertical-align: middle; visibility: hidden;"></span>
            <span id="msh-availability-save-status" style="margin-left: 10px; vertical-align: middle;"></span>
        </p>
    </div>

    <?php // Contenedor del Modal para Disponibilidad (usado por admin-script.js) ?>
    <div id="msh-availability-modal-container" style="display: none;">
         <div id="msh-availability-modal-content">
             <p><?php esc_html_e('Cargando formulario...', 'music-schedule-manager'); ?></p>
         </div>
    </div>
    <?php
}


/**
 * Función auxiliar para renderizar los campos del formulario de disponibilidad (para el modal).
 * Utiliza Checkboxes para selecciones múltiples.
 *
 * @param array $block_data Datos del bloque actual para edición (vacío si es nuevo).
 * @param mixed $index Índice del bloque ('{{INDEX}}' para plantilla JS, número para edición).
 * @return string HTML de los campos del formulario.
 */
function msh_render_disponibilidad_form_fields( $block_data = [], $index = '{{INDEX}}' ) {
    // Cargar listas de CPTs relacionados (idealmente una sola vez)
    // Usamos variables globales aquí por simplicidad, pero pasar como args sería más limpio.
    global $sedes_avail, $programas_avail, $rangos_avail, $dias_semana_avail;

    // Cargar si no existen (evitar múltiples get_posts si se llama varias veces)
    if (!isset($sedes_avail)) $sedes_avail = get_posts( array( 'post_type' => 'msh_sede', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) );
    if (!isset($programas_avail)) $programas_avail = get_posts( array( 'post_type' => 'msh_programa', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) );
    if (!isset($rangos_avail)) $rangos_avail = get_posts( array( 'post_type' => 'msh_rango_edad', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) );
    if (!isset($dias_semana_avail)) $dias_semana_avail = msh_get_dias_semana();

    // Valores por defecto o guardados
    $dia_seleccionado = $block_data['dia'] ?? '';
    $hora_inicio = $block_data['hora_inicio'] ?? '';
    $hora_fin = $block_data['hora_fin'] ?? '';
    // Asegurarse que sean arrays para in_array() y validación
    $sedes_seleccionadas = isset($block_data['sedes']) && is_array($block_data['sedes']) ? array_map('absint', $block_data['sedes']) : [];
    $programas_seleccionados = isset($block_data['programas']) && is_array($block_data['programas']) ? array_map('absint', $block_data['programas']) : [];
    $rangos_seleccionados = isset($block_data['rangos']) && is_array($block_data['rangos']) ? array_map('absint', $block_data['rangos']) : [];

    ob_start();
    ?>
        <input type="hidden" name="block_index" value="<?php echo esc_attr( $index ); ?>">

        <table class="form-table msh-modal-form-table"> <?php // Clase específica ?>
            <tbody>
                 <tr class="form-field">
                    <th><label for="msh_avail_dia"><?php esc_html_e( 'Día', 'music-schedule-manager' ); ?> <span class="description">(req.)</span></label></th>
                    <td><select name="msh_avail_dia" id="msh_avail_dia" required><option value=""><?php esc_html_e('-- Seleccionar --'); ?></option><?php if (!empty($dias_semana_avail)): foreach ($dias_semana_avail as $key => $label): ?><option value="<?php echo esc_attr($key); ?>" <?php selected($dia_seleccionado, $key); ?>><?php echo esc_html($label); ?></option><?php endforeach; endif; ?></select></td>
                </tr>
                 <tr class="form-field">
                    <th><label for="msh_avail_hora_inicio"><?php esc_html_e( 'Hora Inicio', 'music-schedule-manager' ); ?> <span class="description">(req.)</span></label></th>
                    <td><input type="time" name="msh_avail_hora_inicio" id="msh_avail_hora_inicio" value="<?php echo esc_attr($hora_inicio); ?>" required></td>
                </tr>
                 <tr class="form-field">
                    <th><label for="msh_avail_hora_fin"><?php esc_html_e( 'Hora Fin', 'music-schedule-manager' ); ?> <span class="description">(req.)</span></label></th>
                    <td><input type="time" name="msh_avail_hora_fin" id="msh_avail_hora_fin" value="<?php echo esc_attr($hora_fin); ?>" required></td>
                </tr>
                <tr class="form-field">
                    <th style="vertical-align: top;"><label><?php esc_html_e( 'Sedes Admisibles', 'music-schedule-manager' ); ?></label></th>
                    <td><div class="msh-checkbox-list-container"><?php if (!empty($sedes_avail)) : foreach ($sedes_avail as $sede): ?><div class="msh-checkbox-item"><input type="checkbox" name="msh_avail_sedes[]" id="msh_avail_sede_<?php echo esc_attr($sede->ID); ?>" value="<?php echo esc_attr($sede->ID); ?>" <?php checked(in_array($sede->ID, $sedes_seleccionadas)); ?>><label for="msh_avail_sede_<?php echo esc_attr($sede->ID); ?>"><?php echo esc_html($sede->post_title); ?></label></div><?php endforeach; else: ?><p><em><?php esc_html_e('No hay sedes.'); ?></em></p><?php endif; ?></div><p class="description"><?php esc_html_e('Selecciona las sedes aplicables.', 'music-schedule-manager'); ?></p></td>
                </tr>
                <tr class="form-field">
                    <th style="vertical-align: top;"><label><?php esc_html_e( 'Programas Admisibles', 'music-schedule-manager' ); ?></label></th>
                    <td><div class="msh-checkbox-list-container"><?php if (!empty($programas_avail)) : foreach ($programas_avail as $programa): ?><div class="msh-checkbox-item"><input type="checkbox" name="msh_avail_programas[]" id="msh_avail_programa_<?php echo esc_attr($programa->ID); ?>" value="<?php echo esc_attr($programa->ID); ?>" <?php checked(in_array($programa->ID, $programas_seleccionados)); ?>><label for="msh_avail_programa_<?php echo esc_attr($programa->ID); ?>"><?php echo esc_html($programa->post_title); ?></label></div><?php endforeach; else: ?><p><em><?php esc_html_e('No hay programas.'); ?></em></p><?php endif; ?></div><p class="description"><?php esc_html_e('Selecciona los programas aplicables.', 'music-schedule-manager'); ?></p></td>
                </tr>
                 <tr class="form-field">
                    <th style="vertical-align: top;"><label><?php esc_html_e( 'Edades Admisibles', 'music-schedule-manager' ); ?></label></th>
                    <td><div class="msh-checkbox-list-container"><?php if (!empty($rangos_avail)) : foreach ($rangos_avail as $rango): ?><div class="msh-checkbox-item"><input type="checkbox" name="msh_avail_rangos[]" id="msh_avail_rango_<?php echo esc_attr($rango->ID); ?>" value="<?php echo esc_attr($rango->ID); ?>" <?php checked(in_array($rango->ID, $rangos_seleccionados)); ?>><label for="msh_avail_rango_<?php echo esc_attr($rango->ID); ?>"><?php echo esc_html($rango->post_title); ?></label></div><?php endforeach; else: ?><p><em><?php esc_html_e('No hay rangos de edad.'); ?></em></p><?php endif; ?></div><p class="description"><?php esc_html_e('Selecciona los rangos aplicables.', 'music-schedule-manager'); ?></p></td>
                </tr>
            </tbody>
        </table>
    <?php
    return ob_get_clean();
}


// =========================================================================
// =            MANEJADORES AJAX para Disponibilidad General               =
// =========================================================================

/**
 * AJAX Handler: Carga el formulario para añadir/editar un bloque de disponibilidad.
 * Devuelve solo el HTML del formulario.
 */
function msh_ajax_load_disponibilidad_form_handler() {
    // 1. Seguridad: Nonce y Permisos
    check_ajax_referer( 'msh_manage_disponibilidad_action', 'security' );
    $required_capability = apply_filters('msh_capability_manage_availability', 'edit_posts');
    if ( ! current_user_can( $required_capability ) ) {
        wp_send_json_error( array( 'message' => __( 'Permiso denegado.', 'music-schedule-manager' ) ) );
    }

    // 2. Obtener datos de la petición (maestro_id es opcional aquí, block_data es clave)
    $block_data = isset( $_POST['block_data'] ) ? json_decode( wp_unslash( $_POST['block_data'] ), true ) : [];
    $index = isset( $_POST['block_index'] ) ? sanitize_text_field($_POST['block_index']) : -1; // Índice o -1 si es nuevo
    $block_data = is_array($block_data) ? $block_data : []; // Asegurar que sea array

    // 3. Generar HTML del formulario usando el helper
    $form_html = msh_render_disponibilidad_form_fields( $block_data, $index );

    // 4. Envolver en <form> y añadir botones (lo hacemos aquí para tener el contexto $index)
    ob_start();
    ?>
    <form id="msh-availability-form" class="msh-modal-form">
        <h2><?php echo ($index !== -1 && $index !== '{{INDEX}}') ? esc_html__( 'Editar Bloque Disponibilidad', 'music-schedule-manager' ) : esc_html__( 'Añadir Bloque Disponibilidad', 'music-schedule-manager' ); ?></h2>
        <?php echo $form_html; // Campos generados ?>
        <div id="msh-availability-validation-messages" style="color: red; margin-bottom: 10px; display: none;"></div>
        <p class="submit">
            <button type="submit" class="button button-primary msh-save-availability-block-btn"><?php echo ($index !== -1 && $index !== '{{INDEX}}') ? esc_html__( 'Actualizar Bloque', 'music-schedule-manager' ) : esc_html__( 'Añadir Bloque', 'music-schedule-manager' ); ?></button>
            <button type="button" class="button button-secondary msh-cancel-availability-btn"><?php esc_html_e('Cancelar', 'music-schedule-manager'); ?></button>
        </p>
    </form>
    <?php
    $full_form_html = ob_get_clean();

    // 5. Enviar respuesta
    wp_send_json_success( array( 'html' => $full_form_html ) );
}
add_action( 'wp_ajax_msh_load_disponibilidad_form', 'msh_ajax_load_disponibilidad_form_handler' );
// No añadir 'nopriv'


/**
 * AJAX Handler: Guarda TODOS los bloques de Disponibilidad General del maestro.
 * Recibe el array completo desde JavaScript.
 */
function msh_ajax_save_disponibilidad_handler() {
    // 1. Seguridad: Nonce específico de guardado y Permisos
    check_ajax_referer( 'msh_save_disponibilidad_action', 'security' );
     $required_capability_save = apply_filters('msh_capability_manage_availability', 'edit_posts');
     if ( ! current_user_can( $required_capability_save ) ) {
        wp_send_json_error( array( 'message' => __( 'Permiso denegado para guardar.', 'music-schedule-manager' ) ) );
    }

    // 2. Obtener datos
    $maestro_id = isset( $_POST['maestro_id'] ) ? absint( $_POST['maestro_id'] ) : 0;
    $availability_data_json = isset( $_POST['availability_data'] ) ? wp_unslash( $_POST['availability_data'] ) : '[]';
    $availability_data = json_decode( $availability_data_json, true );

    // 3. Validar Maestro ID y datos recibidos
    if ( ! $maestro_id || get_post_type( $maestro_id ) !== 'msh_maestro' ) {
         wp_send_json_error( array( 'message' => __( 'ID de Maestro inválido.', 'music-schedule-manager' ) ) );
    }
    if ( !is_array($availability_data) ) {
        wp_send_json_error( array( 'message' => __( 'Datos de disponibilidad inválidos.', 'music-schedule-manager' ) ) );
    }

    // 4. Sanitización y Validación FUERTE del array completo
    $disponibilidad_sanitizada = array();
    $dias_permitidos = array_keys( msh_get_dias_semana() );
    $slots_unicos = array(); // Para chequeo de duplicados
    $errors = [];

    foreach ( $availability_data as $index => $bloque ) {
        if (!is_array($bloque)) continue;
        $bloque_sanitizado = array();
        $dia_sanitizado = ''; $hora_inicio_sanitizada = '';
        // Sanitizar día
        if ( isset( $bloque['dia'] ) && in_array( $bloque['dia'], $dias_permitidos, true ) ) { $dia_sanitizado = sanitize_key( $bloque['dia'] ); $bloque_sanitizado['dia'] = $dia_sanitizado; }
        else { $errors[] = sprintf(__('Bloque %d: Día inválido.', $index + 1)); continue; }
        // Sanitizar Hora Inicio
        if ( isset( $bloque['hora_inicio'] ) && preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $bloque['hora_inicio']) ) { $hora_inicio_sanitizada = sanitize_text_field( $bloque['hora_inicio'] ); $bloque_sanitizado['hora_inicio'] = $hora_inicio_sanitizada; }
        else { $errors[] = sprintf(__('Bloque %d: Hora inicio inválida.', $index + 1)); continue; }
        // Sanitizar Hora Fin y validar
        if ( isset( $bloque['hora_fin'] ) && preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $bloque['hora_fin']) ) {
            $hora_fin_temp = sanitize_text_field( $bloque['hora_fin'] );
            if (strtotime($hora_fin_temp) > strtotime($hora_inicio_sanitizada)) { $bloque_sanitizado['hora_fin'] = $hora_fin_temp; }
            else { $errors[] = sprintf(__('Bloque %d: Hora fin <= Hora inicio.', $index + 1)); continue; }
        } else { $errors[] = sprintf(__('Bloque %d: Hora fin inválida.', $index + 1)); continue; }
        // Chequeo Duplicados
        $slot_key = $dia_sanitizado . '-' . $hora_inicio_sanitizada;
        if ( isset( $slots_unicos[ $slot_key ] ) ) { $errors[] = sprintf(__('Bloque %d: Horario duplicado.', $index + 1)); continue; }
        $slots_unicos[ $slot_key ] = true;
        // Sanitizar IDs relacionados usando helper
        $bloque_sanitizado['sedes'] = msh_sanitize_post_ids( $bloque['sedes'] ?? [], 'msh_sede' );
        $bloque_sanitizado['programas'] = msh_sanitize_post_ids( $bloque['programas'] ?? [], 'msh_programa' );
        $bloque_sanitizado['rangos'] = msh_sanitize_post_ids( $bloque['rangos'] ?? [], 'msh_rango_edad' );

        $disponibilidad_sanitizada[] = $bloque_sanitizado;
    }

    // Si hubo errores, NO guardar
    if (!empty($errors)) {
         wp_send_json_error( array( 'message' => __('No se guardó. Errores:', 'music-schedule-manager') . '<br>- ' . implode('<br>- ', array_unique($errors)) ) ); // Mostrar errores únicos
    }

    // 5. Guardar en Base de Datos
    // Ordenar antes de guardar
    usort($disponibilidad_sanitizada, 'msh_sort_availability_callback');
    $meta_updated = update_post_meta( $maestro_id, '_msh_maestro_disponibilidad', $disponibilidad_sanitizada );

    // update_post_meta devuelve true si se actualizó, false si no cambió o hubo error, o meta_id si se añadió.
    // Borrar si el array sanitizado quedó vacío
    if ( empty($disponibilidad_sanitizada) ) {
        delete_post_meta( $maestro_id, '_msh_maestro_disponibilidad' );
        $meta_updated = true; // Considerar borrado como éxito
    }

    // 6. Enviar respuesta
    if ( $meta_updated !== false ) { // Considerar 0 o meta_id también como éxito
        wp_send_json_success( array( 'message' => __( 'Disponibilidad guardada correctamente.', 'music-schedule-manager' ) ) );
    } else {
        wp_send_json_error( array( 'message' => __( 'Error al guardar en la base de datos.', 'music-schedule-manager' ) ) );
    }
}
add_action( 'wp_ajax_msh_save_disponibilidad', 'msh_ajax_save_disponibilidad_handler' );
// No añadir 'nopriv'

?>