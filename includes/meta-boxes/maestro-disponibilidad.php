<?php
// includes/meta-boxes/maestro-disponibilidad.php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registra la Meta Box para Disponibilidad del Maestro (UI con tabla y modal).
 */
function msh_add_disponibilidad_meta_box() {
    add_meta_box(
        'msh_maestro_disponibilidad',
        __( 'Disponibilidad General', 'music-schedule-manager' ), // Título ajustado
        'msh_maestro_disponibilidad_metabox_render', // Nombre de función render
        'msh_maestro',
        'normal',
        'default' // Prioridad normal
    );
}
add_action( 'add_meta_boxes_msh_maestro', 'msh_add_disponibilidad_meta_box' );

/**
 * Renderiza el contenido de la Meta Box de Disponibilidad (Tabla + Modal Trigger).
 *
 * @param WP_Post $post El objeto del post actual (Maestro).
 */
function msh_maestro_disponibilidad_metabox_render( $post ) {
    // Nonce para acciones generales (cargar form, etc.)
    wp_nonce_field( 'msh_manage_disponibilidad_action', 'msh_manage_disponibilidad_nonce' );
    // Nonce ESPECÍFICO para la acción de guardar TODO
    wp_nonce_field( 'msh_save_disponibilidad_action', 'msh_save_disponibilidad_nonce' );


    // Obtener datos guardados (se pasarán a JS via localize_script ahora)
    // $disponibilidad_guardada = get_post_meta( $post->ID, '_msh_maestro_disponibilidad', true );
    // $disponibilidad_guardada = is_array( $disponibilidad_guardada ) ? $disponibilidad_guardada : array();

    // Obtener Sedes, Programas, Rangos (necesarios para el helper del formulario)
    // Optimizacion: Podríamos obtenerlos una vez en assets.php y pasarlos a JS si son muchos
    $sedes = get_posts( array( 'post_type' => 'msh_sede', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) );
    $programas = get_posts( array( 'post_type' => 'msh_programa', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) );
    $rangos = get_posts( array( 'post_type' => 'msh_rango_edad', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) );
    $dias_semana = msh_get_dias_semana(); // Helper definido en maestro-clases.php o aquí
    ?>
    <div id="msh-disponibilidad-manager-container">
        <p><?php esc_html_e( 'Define los bloques generales de tiempo en los que este maestro puede estar disponible.', 'music-schedule-manager' ); ?></p>

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
                <tbody id="msh-availability-list">
                    <?php // Las filas se cargarán aquí mediante JavaScript usando los datos localizados ?>
                    <tr id="msh-availability-loading-row">
                        <td colspan="6"><?php esc_html_e( 'Cargando disponibilidad...', 'music-schedule-manager'); ?></td>
                    </tr>
                     <tr id="msh-no-availability-row" style="display: none;">
                         <td colspan="6"><?php esc_html_e('No se han definido bloques de disponibilidad.', 'music-schedule-manager'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p>
            <button type="button" id="msh-add-availability-btn" class="button button-secondary">
                <span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e('Añadir Bloque Disponibilidad', 'music-schedule-manager'); ?>
            </button>
            <button type="button" id="msh-save-availability-changes-btn" class="button button-primary" disabled>
                <?php esc_html_e('Guardar Cambios de Disponibilidad', 'music-schedule-manager'); ?>
            </button>
            <span id="msh-availability-spinner" class="spinner" style="float: none; vertical-align: middle;"></span>
            <span id="msh-availability-save-status" style="margin-left: 10px;"></span>
        </p>
    </div>

    <?php // Contenedor del Modal para Disponibilidad ?>
    <div id="msh-availability-modal-container" style="display: none;">
         <div id="msh-availability-modal-content">
             <p><?php esc_html_e('Cargando formulario...', 'music-schedule-manager'); ?></p>
         </div>
    </div>
    <?php
}


/**
 * Función auxiliar para renderizar los campos del formulario de disponibilidad (para el modal).
 *
 * @param array $block_data Datos del bloque actual para edición (vacío si es nuevo).
 * @param mixed $index Índice del bloque ('{{INDEX}}' para plantilla JS, número para edición).
 * @return string HTML de los campos del formulario.
 */
function msh_render_disponibilidad_form_fields( $block_data = [], $index = '{{INDEX}}' ) {
     // Reutilizar listas obtenidas en la función render o pasarlas como argumento
    // (Asumimos que tenemos acceso a $sedes, $programas, $rangos, $dias_semana)
    global $sedes, $programas, $rangos, $dias_semana; // Acceso simple, podría mejorarse

    // Valores por defecto o guardados
    $dia_seleccionado = $block_data['dia'] ?? '';
    $hora_inicio = $block_data['hora_inicio'] ?? '';
    $hora_fin = $block_data['hora_fin'] ?? '';
    $sedes_seleccionadas = $block_data['sedes'] ?? [];
    $programas_seleccionados = $block_data['programas'] ?? [];
    $rangos_seleccionados = $block_data['rangos'] ?? [];

    ob_start();
    ?>
        <input type="hidden" name="block_index" value="<?php echo esc_attr( $index ); ?>">
        <?php // El nonce de guardado estará fuera del form, en el botón "Guardar Cambios" principal ?>

        <table class="form-table">
            <tbody>
                 <tr class="form-field">
                    <th><label for="msh_avail_dia"><?php esc_html_e( 'Día', 'music-schedule-manager' ); ?> <span class="description">(required)</span></label></th>
                    <td>
                        <select name="msh_avail_dia" id="msh_avail_dia" required>
                            <option value=""><?php esc_html_e('-- Seleccionar --', 'music-schedule-manager'); ?></option>
                            <?php if (!empty($dias_semana)): ?>
                                <?php foreach ($dias_semana as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($dia_seleccionado, $key); ?>><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </td>
                </tr>
                 <tr class="form-field">
                    <th><label for="msh_avail_hora_inicio"><?php esc_html_e( 'Hora Inicio', 'music-schedule-manager' ); ?> <span class="description">(required)</span></label></th>
                    <td><input type="time" name="msh_avail_hora_inicio" id="msh_avail_hora_inicio" value="<?php echo esc_attr($hora_inicio); ?>" required></td>
                </tr>
                 <tr class="form-field">
                    <th><label for="msh_avail_hora_fin"><?php esc_html_e( 'Hora Fin', 'music-schedule-manager' ); ?> <span class="description">(required)</span></label></th>
                    <td><input type="time" name="msh_avail_hora_fin" id="msh_avail_hora_fin" value="<?php echo esc_attr($hora_fin); ?>" required></td>
                </tr>
                 <tr class="form-field">
                    <th><label for="msh_avail_sedes"><?php esc_html_e( 'Sedes Admisibles', 'music-schedule-manager' ); ?></label></th>
                    <td>
                        <select multiple name="msh_avail_sedes[]" id="msh_avail_sedes" style="height: 100px; width: 90%;">
                             <?php if (!empty($sedes)) : ?>
                                <?php foreach ($sedes as $sede): ?>
                                    <option value="<?php echo esc_attr($sede->ID); ?>" <?php selected(in_array($sede->ID, $sedes_seleccionadas)); ?>>
                                        <?php echo esc_html($sede->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                             <?php else: ?>
                                <option value="" disabled><?php esc_html_e('No hay sedes creadas', 'music-schedule-manager'); ?></option>
                             <?php endif; ?>
                        </select>
                         <p class="description"><?php esc_html_e('Mantén Ctrl/Cmd para seleccionar múltiples.', 'music-schedule-manager'); ?></p>
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label for="msh_avail_programas"><?php esc_html_e( 'Programas Admisibles', 'music-schedule-manager' ); ?></label></th>
                    <td>
                         <select multiple name="msh_avail_programas[]" id="msh_avail_programas" style="height: 100px; width: 90%;">
                             <?php if (!empty($programas)) : ?>
                                <?php foreach ($programas as $programa): ?>
                                    <option value="<?php echo esc_attr($programa->ID); ?>" <?php selected(in_array($programa->ID, $programas_seleccionados)); ?>>
                                        <?php echo esc_html($programa->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                              <?php else: ?>
                                <option value="" disabled><?php esc_html_e('No hay programas creados', 'music-schedule-manager'); ?></option>
                             <?php endif; ?>
                        </select>
                        <p class="description"><?php esc_html_e('Mantén Ctrl/Cmd para seleccionar múltiples.', 'music-schedule-manager'); ?></p>
                    </td>
                </tr>
                 <tr class="form-field">
                    <th><label for="msh_avail_rangos"><?php esc_html_e( 'Edades Admisibles', 'music-schedule-manager' ); ?></label></th>
                    <td>
                         <select multiple name="msh_avail_rangos[]" id="msh_avail_rangos" style="height: 100px; width: 90%;">
                             <?php if (!empty($rangos)) : ?>
                                <?php foreach ($rangos as $rango): ?>
                                    <option value="<?php echo esc_attr($rango->ID); ?>" <?php selected(in_array($rango->ID, $rangos_seleccionados)); ?>>
                                        <?php echo esc_html($rango->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                             <?php else: ?>
                                <option value="" disabled><?php esc_html_e('No hay rangos creados', 'music-schedule-manager'); ?></option>
                             <?php endif; ?>
                        </select>
                         <p class="description"><?php esc_html_e('Mantén Ctrl/Cmd para seleccionar múltiples.', 'music-schedule-manager'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
    <?php
    return ob_get_clean();
}


// --- AJAX Handler para Cargar el Formulario de Disponibilidad ---
function msh_ajax_load_disponibilidad_form_handler() {
    // 1. Seguridad
    check_ajax_referer( 'msh_manage_disponibilidad_action', 'security' );
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permiso denegado.', 'music-schedule-manager' ) ) );
    }

    // 2. Obtener datos (maestro_id y datos del bloque si es edición)
    $maestro_id = isset( $_POST['maestro_id'] ) ? absint( $_POST['maestro_id'] ) : 0;
    // Los datos del bloque se pasarán directamente como un objeto JSON desde JS
    $block_data = isset( $_POST['block_data'] ) ? json_decode( wp_unslash( $_POST['block_data'] ), true ) : [];
    $index = isset( $_POST['block_index'] ) ? sanitize_text_field($_POST['block_index']) : -1; // Índice o -1 si es nuevo

    if ( ! $maestro_id || get_post_type( $maestro_id ) !== 'msh_maestro' ) {
         wp_send_json_error( array( 'message' => __( 'ID de Maestro inválido.', 'music-schedule-manager' ) ) );
    }

    // Sanitizar $block_data (básico aquí, la validación fuerte es al guardar)
    $block_data = is_array($block_data) ? $block_data : [];

    // 3. Generar HTML del formulario usando el helper
    // Hay que asegurarse que $sedes, $programas, $rangos, $dias_semana estén disponibles globalmente o cargarlos aquí.
    global $sedes, $programas, $rangos, $dias_semana;
    if (!isset($sedes)) $sedes = get_posts( array( 'post_type' => 'msh_sede', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) );
    if (!isset($programas)) $programas = get_posts( array( 'post_type' => 'msh_programa', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) );
    if (!isset($rangos)) $rangos = get_posts( array( 'post_type' => 'msh_rango_edad', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) );
    if (!isset($dias_semana)) $dias_semana = msh_get_dias_semana(); // Asume que esta función existe (puede estar en maestro-clases.php)

    $form_fields_html = msh_render_disponibilidad_form_fields( $block_data, $index );

    // 4. Envolver en formulario y añadir botones
    ob_start();
    ?>
    <form id="msh-availability-form">
        <h2><?php echo ($index !== -1 && $index !== '{{INDEX}}') ? esc_html__( 'Editar Bloque de Disponibilidad', 'music-schedule-manager' ) : esc_html__( 'Añadir Bloque de Disponibilidad', 'music-schedule-manager' ); ?></h2>

        <?php echo $form_fields_html; // Campos generados por el helper ?>

        <div id="msh-availability-validation-messages" style="color: red; margin-bottom: 10px;"></div>

        <p class="submit">
            <button type="submit" class="button button-primary msh-save-availability-block-btn">
                <?php echo ($index !== -1 && $index !== '{{INDEX}}') ? esc_html__( 'Actualizar Bloque', 'music-schedule-manager' ) : esc_html__( 'Añadir Bloque', 'music-schedule-manager' ); ?>
            </button>
            <button type="button" class="button button-secondary msh-cancel-availability-btn">
                <?php esc_html_e('Cancelar', 'music-schedule-manager'); ?>
            </button>
             <?php // No necesitamos spinner aquí, el guardado final es con el otro botón ?>
        </p>
    </form>
    <?php
    $form_html = ob_get_clean();

    // 5. Enviar respuesta
    wp_send_json_success( array( 'html' => $form_html ) );
}
add_action( 'wp_ajax_msh_load_disponibilidad_form', 'msh_ajax_load_disponibilidad_form_handler' );


// --- AJAX Handler para Guardar TODOS los bloques de Disponibilidad ---
function msh_ajax_save_disponibilidad_handler() {
    // 1. Seguridad: Usar el nonce específico de guardado
    check_ajax_referer( 'msh_save_disponibilidad_action', 'security' );
     if ( ! current_user_can( 'edit_posts' ) ) { // Ajustar capacidad si es necesario
        wp_send_json_error( array( 'message' => __( 'Permiso denegado.', 'music-schedule-manager' ) ) );
    }

    // 2. Obtener datos
    $maestro_id = isset( $_POST['maestro_id'] ) ? absint( $_POST['maestro_id'] ) : 0;
    // Recibir el array completo de disponibilidad desde JS (esperado como JSON string)
    $availability_data_json = isset( $_POST['availability_data'] ) ? wp_unslash( $_POST['availability_data'] ) : '[]';
    $availability_data = json_decode( $availability_data_json, true );

    if ( ! $maestro_id || get_post_type( $maestro_id ) !== 'msh_maestro' ) {
         wp_send_json_error( array( 'message' => __( 'ID de Maestro inválido.', 'music-schedule-manager' ) ) );
    }

    if ( !is_array($availability_data) ) {
        wp_send_json_error( array( 'message' => __( 'Datos de disponibilidad inválidos recibidos.', 'music-schedule-manager' ) ) );
    }

    // 3. Sanitización y Validación FUERTE del array completo
    $disponibilidad_sanitizada = array();
    $dias_permitidos = array_keys( msh_get_dias_semana() ); // Reutilizar helper
    $slots_unicos = array(); // Para chequeo de duplicados DENTRO del set enviado
    $errors = [];

    foreach ( $availability_data as $index => $bloque ) {
        if (!is_array($bloque)) continue; // Ignorar elementos no válidos

        $bloque_sanitizado = array();
        $dia_sanitizado = '';
        $hora_inicio_sanitizada = '';

        // Sanitizar día
        if ( isset( $bloque['dia'] ) && in_array( $bloque['dia'], $dias_permitidos, true ) ) {
            $dia_sanitizado = sanitize_key( $bloque['dia'] );
            $bloque_sanitizado['dia'] = $dia_sanitizado;
        } else {
            $errors[] = sprintf(__('Error en bloque %d: Día inválido o faltante.', 'music-schedule-manager'), $index + 1);
            continue;
        }

        // Sanitizar Hora Inicio
        if ( isset( $bloque['hora_inicio'] ) && preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $bloque['hora_inicio']) ) {
             $hora_inicio_sanitizada = sanitize_text_field( wp_strip_all_tags( $bloque['hora_inicio'] ) );
             $bloque_sanitizado['hora_inicio'] = $hora_inicio_sanitizada;
        } else {
             $errors[] = sprintf(__('Error en bloque %d: Hora de inicio inválida o faltante (HH:MM).', 'music-schedule-manager'), $index + 1);
             continue;
        }

        // Sanitizar Hora Fin y validar que sea posterior a inicio
        if ( isset( $bloque['hora_fin'] ) && preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $bloque['hora_fin']) ) {
            $hora_fin_temp = sanitize_text_field( wp_strip_all_tags( $bloque['hora_fin'] ) );
            if (strtotime($hora_fin_temp) > strtotime($hora_inicio_sanitizada)) {
                $bloque_sanitizado['hora_fin'] = $hora_fin_temp;
            } else {
                 $errors[] = sprintf(__('Error en bloque %d: La hora de fin debe ser posterior a la hora de inicio.', 'music-schedule-manager'), $index + 1);
                 continue;
            }
        } else {
             $errors[] = sprintf(__('Error en bloque %d: Hora de fin inválida o faltante (HH:MM).', 'music-schedule-manager'), $index + 1);
             continue;
        }

        // Chequeo de Duplicados (Día + Hora Inicio)
        $slot_key = $dia_sanitizado . '-' . $hora_inicio_sanitizada;
        if ( isset( $slots_unicos[ $slot_key ] ) ) {
             $errors[] = sprintf(__('Error en bloque %d: Horario duplicado detectado (mismo día y hora de inicio que otro bloque).', 'music-schedule-manager'), $index + 1);
            continue; // Saltar este bloque duplicado
        }
        $slots_unicos[ $slot_key ] = true;

        // Sanitizar Sedes, Programas, Rangos (usando helper si existe)
        if (function_exists('msh_sanitize_post_ids')) {
             $bloque_sanitizado['sedes'] = msh_sanitize_post_ids( $bloque['sedes'] ?? [], 'msh_sede' );
             $bloque_sanitizado['programas'] = msh_sanitize_post_ids( $bloque['programas'] ?? [], 'msh_programa' );
             $bloque_sanitizado['rangos'] = msh_sanitize_post_ids( $bloque['rangos'] ?? [], 'msh_rango_edad' );
        } else {
            // Fallback si la función no está disponible (debería estarlo)
             $bloque_sanitizado['sedes'] = array_map('absint', $bloque['sedes'] ?? []);
             $bloque_sanitizado['programas'] = array_map('absint', $bloque['programas'] ?? []);
             $bloque_sanitizado['rangos'] = array_map('absint', $bloque['rangos'] ?? []);
        }


        $disponibilidad_sanitizada[] = $bloque_sanitizado;
    } // Fin foreach

    // Si hubo errores de validación/sanitización, NO guardar y devolver errores
    if (!empty($errors)) {
         wp_send_json_error( array(
            'message' => __('No se pudo guardar la disponibilidad debido a los siguientes errores:', 'music-schedule-manager') . '<br>' . implode('<br>', $errors)
        ) );
         return;
    }

    // 4. Guardar en Base de Datos
    if ( ! empty( $disponibilidad_sanitizada ) ) {
        // Ordenar antes de guardar (opcional, pero bueno para consistencia)
        usort($disponibilidad_sanitizada, 'msh_sort_availability_callback'); // Necesitamos esta función de comparación
        update_post_meta( $maestro_id, '_msh_maestro_disponibilidad', $disponibilidad_sanitizada );
    } else {
        // Si el array final está vacío, borrar el meta
        delete_post_meta( $maestro_id, '_msh_maestro_disponibilidad' );
    }

    // 5. Enviar respuesta de éxito
    wp_send_json_success( array( 'message' => __( 'Disponibilidad guardada correctamente.', 'music-schedule-manager' ) ) );
}
add_action( 'wp_ajax_msh_save_disponibilidad', 'msh_ajax_save_disponibilidad_handler' );


/**
 * Función de comparación para ordenar los bloques de disponibilidad
 * por día (L-D) y luego hora de inicio. Usada con usort().
 */
function msh_sort_availability_callback($a, $b) {
    $dias_orden = array_flip(array_keys(msh_get_dias_semana())); // Reutilizar helper

    $dia_a = $a['dia'] ?? '';
    $dia_b = $b['dia'] ?? '';

    $orden_dia_a = isset($dias_orden[$dia_a]) ? $dias_orden[$dia_a] : 99;
    $orden_dia_b = isset($dias_orden[$dia_b]) ? $dias_orden[$dia_b] : 99;

    if ($orden_dia_a != $orden_dia_b) {
        return $orden_dia_a - $orden_dia_b; // Ordenar por día
    }

    // Si el día es el mismo, ordenar por hora de inicio
    $hora_a = $a['hora_inicio'] ?? '99:99';
    $hora_b = $b['hora_inicio'] ?? '99:99';

    $time_a = strtotime($hora_a);
    $time_b = strtotime($hora_b);

    // Manejar posibles errores de strtotime
    if ($time_a === false && $time_b === false) return 0;
    if ($time_a === false) return 1; // Poner inválidos al final
    if ($time_b === false) return -1;

    return $time_a - $time_b;
}


// --- ELIMINAR el hook save_post para disponibilidad ---
// remove_action( 'save_post_msh_maestro', 'msh_save_maestro_disponibilidad_meta' ); // Ya no se necesita

// --- ELIMINAR la función msh_save_maestro_disponibilidad_meta ---
// (Asegúrate de borrarla completamente)

// --- ELIMINAR la función msh_display_admin_notices ---
// (El aviso de duplicados se manejará en JS o en la respuesta del AJAX de guardado)
// remove_action( 'admin_notices', 'msh_display_admin_notices' );

// --- Asegurarse que msh_get_dias_semana existe ---
// if (!function_exists('msh_get_dias_semana')) {
//     function msh_get_dias_semana() {
//         return array(
//             'lunes'     => __( 'Lunes', 'music-schedule-manager' ),
//             'martes'    => __( 'Martes', 'music-schedule-manager' ),
//             'miercoles' => __( 'Miércoles', 'music-schedule-manager' ),
//             'jueves'    => __( 'Jueves', 'music-schedule-manager' ),
//             'viernes'   => __( 'Viernes', 'music-schedule-manager' ),
//             'sabado'    => __( 'Sábado', 'music-schedule-manager' ),
//             'domingo'   => __( 'Domingo', 'music-schedule-manager' ),
//         );
//     }
// }
// --- Asegurarse que msh_sanitize_post_ids existe ---
if (!function_exists('msh_sanitize_post_ids')) {
    function msh_sanitize_post_ids( $ids, string $post_type ): array {
        if ( ! is_array( $ids ) ) { return []; }
        $sanitized_ids = [];
        foreach ( $ids as $id ) {
            $int_id = absint( $id );
            if ( $int_id > 0 && get_post_type( $int_id ) === $post_type && get_post_status( $int_id ) === 'publish' ) {
                $sanitized_ids[] = $int_id;
            }
        }
        return array_unique( $sanitized_ids );
    }
}

?>