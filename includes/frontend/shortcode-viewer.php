<?php
// includes/frontend/shortcode-viewer.php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registra el shortcode [music_schedule_viewer].
 */
function msh_register_viewer_shortcode() {
    add_shortcode( 'music_schedule_viewer', 'msh_render_schedule_viewer_shortcode' );
}
add_action( 'init', 'msh_register_viewer_shortcode' );

/**
 * Función callback para el shortcode [music_schedule_viewer].
 * Encola scripts/estilos y muestra la estructura HTML inicial.
 */
function msh_render_schedule_viewer_shortcode( $atts ) {
    // Encolar Assets del Frontend
    wp_enqueue_style('thickbox');
    wp_enqueue_script('thickbox');
    wp_enqueue_style('msh-frontend-styles', MSH_PLUGIN_URL . 'assets/css/frontend-styles.css', array('thickbox'), MSH_VERSION);
    
    // *** Encolar Draggable y asegurar jQuery UI Core/Widget/Mouse ***
    wp_enqueue_script( 'jquery-ui-draggable', false, array('jquery', 'jquery-ui-core', 'jquery-ui-mouse', 'jquery-ui-widget'), null, true );
    // *** Fin Encolar Draggable ***

    wp_enqueue_script('msh-frontend-script', MSH_PLUGIN_URL . 'assets/js/frontend-script.js', array('jquery', 'thickbox'), MSH_VERSION, true);
    

    // Obtener Mapas ID -> Título usando el helper
    $sede_map = msh_get_cpt_id_title_map('msh_sede');
    $programa_map = msh_get_cpt_id_title_map('msh_programa');
    $rango_map = msh_get_cpt_id_title_map('msh_rango_edad');
    $maestro_map = msh_get_cpt_id_title_map('msh_maestro');

    // *** DEBUG LOG: Verificar Mapas en Frontend ***
    // error_log("[MSH Frontend] Mapas Cargados - Sedes: " . count($sede_map) . ", Programas: " . count($programa_map) . ", Rangos: " . count($rango_map) . ", Maestros: " . count($maestro_map));

    // Pasar datos a JavaScript del Frontend
    wp_localize_script( 'msh-frontend-script', 'msh_frontend_data', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'msh_filter_schedule_nonce' ), // Para filtros
        'manage_clases_nonce' => wp_create_nonce( 'msh_manage_clases_action' ), // Para cargar modal clase

        // *** Pasar el CAMPO HTML del nonce de guardado ***
        'save_clase_nonce_field' => wp_nonce_field( 'msh_save_clase_action', 'msh_save_clase_nonce', true, false ),

        // Mapas de Nombres
        'sede_names' => $sede_map, 'programa_names' => $programa_map, 'rango_names' => $rango_map, 'maestro_names' => $maestro_map,

        
        // Textos traducibles
        'loading_message' => esc_html__( 'Buscando horarios...', 'music-schedule-manager' ),
        'error_message'   => esc_html__( 'Ocurrió un error. Por favor, inténtalo de nuevo.', 'music-schedule-manager' ),
        'no_results_message' => esc_html__( 'No se encontraron horarios que coincidan con tu búsqueda.', 'music-schedule-manager' ),
        'modal_title_manage_clase' => __( 'Editar Clase', 'music-schedule-manager' ),
        'modal_title_assign_clase' => __( 'Asignar Nuevo Horario', 'music-schedule-manager' ),
        'modal_loading_form' => __( 'Cargando...', 'music-schedule-manager' ),
        'modal_error_loading' => __( 'Error al cargar.', 'music-schedule-manager' ),
        'modal_error_saving' => __( 'Error al guardar.', 'music-schedule-manager' ),
        'validation_end_after_start' => __( 'Hora fin debe ser posterior a inicio.', 'music-schedule-manager' ),
        'availability_hint_text' => __( 'No disponible/admisible.', 'music-schedule-manager' ),
        'days_of_week' => msh_get_dias_semana(),
    ));

    // HTML del Shortcode
    ob_start();
    ?>
    <div class="msh-schedule-viewer-wrapper">
        <h2><?php esc_html_e( 'Consultar Disponibilidad de Horarios', 'music-schedule-manager' ); ?></h2>
        <?php echo msh_render_filter_form(); // Renderizar formulario ?>
        <div id="msh-schedule-results-wrapper">
            <h3><?php esc_html_e( 'Resultados', 'music-schedule-manager' ); ?></h3>
            <div id="msh-schedule-results-feedback" style="display: none;"></div>
            <div id="msh-schedule-results-table-container">
                <p id="msh-initial-message"><?php esc_html_e( 'Utiliza los filtros para buscar horarios.', 'music-schedule-manager' ); ?></p>
            </div>
        </div>
        <?php // Contenedor Modal para Frontend (necesario para Thickbox) ?>
        <div id="msh-frontend-clase-modal-container" style="display: none;">
             <div id="msh-frontend-clase-modal-content"><p><?php esc_html_e('Cargando...', 'music-schedule-manager'); ?></p></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}


/**
 * Renderiza el HTML del formulario de filtros.
 */
function msh_render_filter_form() {
    ob_start();
    ?>
    <form id="msh-schedule-filters-form">
        <div class="msh-filter-row">
            <div class="msh-filter-group"><label for="msh-filter-maestro"><?php esc_html_e('Maestro:'); ?></label><select name="msh_filter_maestro" id="msh-filter-maestro"><option value=""><?php esc_html_e('Todos'); ?></option><?php echo msh_get_cpt_options_html('msh_maestro'); ?></select></div>
            <div class="msh-filter-group"><label for="msh-filter-programa"><?php esc_html_e('Programa:'); ?></label><select name="msh_filter_programa" id="msh-filter-programa"><option value=""><?php esc_html_e('Todos'); ?></option><?php echo msh_get_cpt_options_html('msh_programa'); ?></select></div>
            <div class="msh-filter-group"><label for="msh-filter-sede"><?php esc_html_e('Sede:'); ?></label><select name="msh_filter_sede" id="msh-filter-sede"><option value=""><?php esc_html_e('Todas'); ?></option><?php echo msh_get_cpt_options_html('msh_sede'); ?></select></div>
        </div>
        <div class="msh-filter-row">
            <div class="msh-filter-group"><label for="msh-filter-rango_edad"><?php esc_html_e('Rango Edad:'); ?></label><select name="msh_filter_rango_edad" id="msh-filter-rango_edad"><option value=""><?php esc_html_e('Todos'); ?></option><?php echo msh_get_cpt_options_html('msh_rango_edad'); ?></select></div>
            <div class="msh-filter-group"><label for="msh-filter-dia"><?php esc_html_e('Día:'); ?></label><select name="msh_filter_dia" id="msh-filter-dia"><option value=""><?php esc_html_e('Cualquier Día'); ?></option><?php $dias = msh_get_dias_semana(); foreach ($dias as $key => $label) echo '<option value="'.esc_attr($key).'">'.esc_html($label).'</option>'; ?></select></div>
            <div class="msh-filter-group"><label for="msh-filter-hora_inicio"><?php esc_html_e('Desde (Hora):'); ?></label><input type="time" name="msh_filter_hora_inicio" id="msh-filter-hora_inicio" step="1800"></div>
        </div>
        <div class="msh-filter-actions">
            <button type="submit" class="button msh-filter-submit-btn"><?php esc_html_e('Buscar Horarios'); ?></button>
            <button type="reset" class="button msh-filter-reset-btn"><?php esc_html_e('Limpiar Filtros'); ?></button>
        </div>
    </form>
    <?php
    return ob_get_clean();
}


/**
 * Realiza la consulta compleja para obtener horarios disponibles y asignados.
 * Incluye logs de depuración.
 *
 * @param array $filters Array asociativo con los filtros aplicados.
 * @return array Array de resultados formateados para la tabla frontend.
 */
function msh_perform_complex_schedule_query( $filters = array() ) {
    error_log("-------------------- MSH Query Start --------------------");
    error_log("[MSH Query LOG 1] Filters Received: " . print_r($filters, true));

    $final_results = array();

    // 1. Obtener Maestros Relevantes
    $maestro_args = array(
        'post_type' => 'msh_maestro',
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids',
        'orderby' => 'title',
        'order' => 'ASC'
    );
    if ( !empty($filters['maestro_id']) ) {
        $maestro_args['post__in'] = array( $filters['maestro_id'] );
    }
    $maestro_ids = get_posts( $maestro_args );
    error_log("[MSH Query LOG 2] Maestro IDs Found: " . print_r($maestro_ids, true));

    if ( empty($maestro_ids) ) {
        error_log("[MSH Query END] No matching maestros found.");
        error_log("-------------------- MSH Query End ----------------------");
        return $final_results;
    }

    // Helper para convertir HH:MM a minutos desde medianoche
    $time_to_minutes = function($time_str) {
        if (empty($time_str) || !preg_match('/^(\d{1,2}):(\d{2})$/', $time_str, $matches)) return false;
        return intval($matches[1]) * 60 + intval($matches[2]);
    };

    // 2. Iterar por cada Maestro
    foreach ( $maestro_ids as $maestro_id ) {
        error_log("[MSH Query LOG 3] Processing Maestro ID: " . $maestro_id);

        // 2a. Obtener Disponibilidad General del Maestro
        $availability_blocks_raw = get_post_meta( $maestro_id, '_msh_maestro_disponibilidad', true );
        $availability_blocks = is_array($availability_blocks_raw) ? $availability_blocks_raw : [];
        // error_log("[MSH Query LOG 4] Maestro $maestro_id - Raw Availability: " . print_r($availability_blocks, true)); // Puede ser muy largo

        // Filtrar disponibilidad por día si se especificó
        if ( !empty($filters['dia']) ) {
            $original_count = count($availability_blocks);
            $availability_blocks = array_filter($availability_blocks, fn($b) => isset($b['dia']) && $b['dia'] === $filters['dia']);
            error_log("[MSH Query LOG 5] Maestro $maestro_id - Availability filtered by Day '{$filters['dia']}': " . count($availability_blocks) . " blocks (from " . $original_count . ")");
        } else {
             error_log("[MSH Query LOG 5] Maestro $maestro_id - No Day filter applied. Total Avail Blocks: " . count($availability_blocks));
        }

        if ( empty($availability_blocks) ) {
            error_log("[MSH Query] Maestro $maestro_id - No availability blocks remaining for this day/filter.");
            continue; // Saltar al siguiente maestro
        }

        // 2b. Obtener Clases Programadas del Maestro (filtrando por día si es posible)
        $clase_meta_query = array(
            'relation' => 'AND',
            array( 'key' => '_msh_clase_maestro_id', 'value' => $maestro_id, 'compare' => '=', 'type' => 'NUMERIC' ),
        );
        if (!empty($filters['dia'])) {
            $clase_meta_query[] = array( 'key' => '_msh_clase_dia', 'value' => $filters['dia'], 'compare' => '=' );
        }
        $clase_args = array(
            'post_type' => 'msh_clase', 'post_status' => 'publish', 'numberposts' => -1, 'meta_query' => $clase_meta_query
        );
        $scheduled_classes_posts = get_posts( $clase_args );

        // Crear un mapa de clases por día para acceso rápido y ordenado
        $classes_by_day = array();
        foreach ($scheduled_classes_posts as $clase_post) {
             $clase_meta = get_post_meta($clase_post->ID);
             $dia = $clase_meta['_msh_clase_dia'][0] ?? null;
             if ($dia) {
                 $classes_by_day[$dia][] = array(
                     'id'          => $clase_post->ID,
                     'hora_inicio' => $clase_meta['_msh_clase_hora_inicio'][0] ?? '',
                     'hora_fin'    => $clase_meta['_msh_clase_hora_fin'][0] ?? '',
                     'programa_id' => absint($clase_meta['_msh_clase_programa_id'][0] ?? 0),
                     'sede_id'     => absint($clase_meta['_msh_clase_sede_id'][0] ?? 0),
                     'rango_id'    => absint($clase_meta['_msh_clase_rango_id'][0] ?? 0),
                     'capacidad'   => absint($clase_meta['_msh_clase_capacidad'][0] ?? 1),
                     'inscritos'   => 0, // Placeholder - Implementar si es necesario
                 );
                 // Ordenar clases dentro del día
                 if (count($classes_by_day[$dia]) > 1) {
                    usort($classes_by_day[$dia], function($a, $b) use ($time_to_minutes) {
                        return ($time_to_minutes($a['hora_inicio']) ?: 9999) - ($time_to_minutes($b['hora_inicio']) ?: 9999);
                    });
                 }
             }
        }
        error_log("[MSH Query LOG 6] Maestro $maestro_id - Scheduled Classes Found (by day): " . print_r(array_map('count', $classes_by_day), true)); // Solo contar clases por día

        // 3. Procesar cada Bloque de Disponibilidad General
        foreach ($availability_blocks as $i => $avail_block) {
             // error_log("[MSH Query LOG 7] Maestro $maestro_id - Processing Avail Block #$i: " . print_r($avail_block, true)); // Puede ser muy largo
             $current_day = $avail_block['dia'] ?? null;
             if (!$current_day) { error_log("[MSH Query] Maestro $maestro_id - Avail Block #$i MISSING DAY."); continue; }

            $avail_start_min = $time_to_minutes($avail_block['hora_inicio'] ?? '');
            $avail_end_min = $time_to_minutes($avail_block['hora_fin'] ?? '');
            if ($avail_start_min === false || $avail_end_min === false || $avail_end_min <= $avail_start_min) {
                 error_log("[MSH Query] Maestro $maestro_id - Avail Block #$i invalid time: Start={$avail_block['hora_inicio']}, End={$avail_block['hora_fin']}.");
                 continue;
            }
            error_log("[MSH Query LOG 7.1] Maestro $maestro_id - Avail Block #$i Day: {$current_day}, Time: {$avail_block['hora_inicio']}-{$avail_block['hora_fin']} ({$avail_start_min}-{$avail_end_min} mins)");

            // Obtener clases relevantes para ESTE día
            $relevant_classes = $classes_by_day[$current_day] ?? [];
            // error_log("[MSH Query LOG 8] Maestro $maestro_id - Avail Block #$i - Relevant Classes for Day {$current_day}: " . print_r($relevant_classes, true)); // Puede ser largo

            // 4. Calcular Huecos (Slots Vacíos y Asignados)
            $current_pointer_min = $avail_start_min; // Inicio del bloque de disponibilidad

            foreach ($relevant_classes as $clase) {
                $clase_start_min = $time_to_minutes($clase['hora_inicio']);
                $clase_end_min = $time_to_minutes($clase['hora_fin']);

                 // Validar clase y solapamiento básico con el bloque general
                 if ($clase_start_min === false || $clase_end_min === false || $clase_end_min <= $clase_start_min || $clase_start_min >= $avail_end_min || $clase_end_min <= $avail_start_min) {
                     error_log("[MSH Query] Maestro $maestro_id - Class ID {$clase['id']} invalid time or outside avail block. Skipping.");
                     continue; // Ignorar clase inválida o que no solapa
                 }
                 // Ajustar si la clase empieza antes o termina después del bloque actual (no debería pasar si la validación al guardar es correcta)
                 $effective_clase_start = max($clase_start_min, $avail_start_min);
                 $effective_clase_end = min($clase_end_min, $avail_end_min);

                // 4a. ¿Hueco Vacío ANTES de esta clase?
                if ($effective_clase_start > $current_pointer_min) {
                    $slot_vacio_antes = array(
                        'type' => 'vacio', 'dia' => $current_day,
                        'hora_inicio' => date('H:i', mktime(0, $current_pointer_min)),
                        'hora_fin' => date('H:i', mktime(0, $effective_clase_start)),
                        'maestro_id' => $maestro_id,
                        'programas_admisibles' => $avail_block['programas'] ?? [],
                        'sedes_admisibles' => $avail_block['sedes'] ?? [],
                        'rangos_admisibles' => $avail_block['rangos'] ?? []
                    );
                    $final_results[] = $slot_vacio_antes;
                    error_log("[MSH Query LOG 9.1] Maestro $maestro_id - ADDED Vacío (Before Class {$clase['id']}): {$slot_vacio_antes['hora_inicio']}-{$slot_vacio_antes['hora_fin']}");
                }

                // 4b. Slot ASIGNADO (la clase)
                 $slot_asignado = array(
                     'type' => 'asignado', 'dia' => $current_day,
                     'hora_inicio' => $clase['hora_inicio'], // Usar hora original de la clase
                     'hora_fin' => $clase['hora_fin'],   // Usar hora original de la clase
                     'maestro_id' => $maestro_id, 'programa_id' => $clase['programa_id'],
                     'sede_id' => $clase['sede_id'], 'rango_id' => $clase['rango_id'],
                     'capacidad' => $clase['capacidad'], 'inscritos' => $clase['inscritos'] ?? 0,
                     'clase_id' => $clase['id']
                 );
                 $final_results[] = $slot_asignado;
                 error_log("[MSH Query LOG 9.2] Maestro $maestro_id - ADDED Asignado (Class {$clase['id']}): {$slot_asignado['hora_inicio']}-{$slot_asignado['hora_fin']}");

                 // Mover el puntero al final EFECTIVO de esta clase dentro del bloque
                 $current_pointer_min = max($current_pointer_min, $effective_clase_end); // Avanzar puntero

            } // Fin loop clases solapadas

            // 4c. ¿Hueco Vacío DESPUÉS de la última clase (o si no hubo clases)?
            if ($current_pointer_min < $avail_end_min) {
                 $slot_vacio_despues = array(
                     'type' => 'vacio', 'dia' => $current_day,
                     'hora_inicio' => date('H:i', mktime(0, $current_pointer_min)),
                     'hora_fin' => date('H:i', mktime(0, $avail_end_min)), // Hasta el final del bloque de disponibilidad
                     'maestro_id' => $maestro_id,
                     'programas_admisibles' => $avail_block['programas'] ?? [],
                     'sedes_admisibles' => $avail_block['sedes'] ?? [],
                     'rangos_admisibles' => $avail_block['rangos'] ?? []
                 );
                 $final_results[] = $slot_vacio_despues;
                 error_log("[MSH Query LOG 9.3] Maestro $maestro_id - ADDED Vacío (After Last Class / No Classes): {$slot_vacio_despues['hora_inicio']}-{$slot_vacio_despues['hora_fin']}");
            }
            // error_log("[MSH Query LOG 10] Maestro $maestro_id - Finished Processing Avail Block #$i.");
        } // Fin loop bloques disponibilidad
         // error_log("[MSH Query LOG 11] Maestro $maestro_id - Finished Processing.");
    } // Fin loop maestros

    error_log("[MSH Query LOG 12] Raw results generated BEFORE final filtering (" . count($final_results) . " items)");
    // error_log(print_r($final_results, true)); // Descomentar con cuidado, puede ser MUY largo

    // 5. Aplicar Filtros Finales (Programa, Sede, Rango, Hora Inicio)
    if (!empty($filters['programa_id']) || !empty($filters['sede_id']) || !empty($filters['rango_id']) || !empty($filters['hora_inicio'])) {
        $filter_prog = !empty($filters['programa_id']) ? $filters['programa_id'] : null;
        $filter_sede = !empty($filters['sede_id']) ? $filters['sede_id'] : null;
        $filter_rango = !empty($filters['rango_id']) ? $filters['rango_id'] : null;
        $filter_start_min = !empty($filters['hora_inicio']) ? $time_to_minutes($filters['hora_inicio']) : null;

        $filtered_results = array_filter($final_results, function($slot) use ($filter_prog, $filter_sede, $filter_rango, $filter_start_min, $time_to_minutes) {
            $passes = true;
            // Programa
            if ($passes && $filter_prog) {
                if ($slot['type'] === 'asignado') { $passes = ($slot['programa_id'] == $filter_prog); }
                else { $passes = in_array($filter_prog, $slot['programas_admisibles']); }
            }
            // Sede
            if ($passes && $filter_sede) {
                if ($slot['type'] === 'asignado') { $passes = ($slot['sede_id'] == $filter_sede); }
                else { $passes = in_array($filter_sede, $slot['sedes_admisibles']); }
            }
            // Rango
            if ($passes && $filter_rango) {
                if ($slot['type'] === 'asignado') { $passes = ($slot['rango_id'] == $filter_rango); }
                else { $passes = in_array($filter_rango, $slot['rangos_admisibles']); }
            }
            // Hora Inicio
            if ($passes && $filter_start_min !== null) {
                $slot_start_min = $time_to_minutes($slot['hora_inicio']);
                $passes = ($slot_start_min !== false && $slot_start_min >= $filter_start_min);
            }
            return $passes;
        });
         error_log("[MSH Query LOG 13] Final results AFTER filtering (" . count($filtered_results) . " items)");
         // error_log(print_r($filtered_results, true)); // Descomentar con cuidado
    } else {
        $filtered_results = $final_results; // No aplicar filtros si no se especificaron
         error_log("[MSH Query LOG 13] No specific filters applied (Prog/Sede/Rango/Time). Returning all " . count($filtered_results) . " generated slots.");
    }


    error_log("-------------------- MSH Query End ----------------------");

    // 6. Re-indexar array y devolver
    return array_values($filtered_results);
}


/**
 * Manejador AJAX para filtrar los horarios.
 */
function msh_ajax_filter_schedule_handler() {
    // 1. Seguridad
    check_ajax_referer( 'msh_filter_schedule_nonce', 'nonce' );

    // 2. Obtener y Sanitizar Filtros
    $filters = array();
    $filters['maestro_id'] = isset( $_POST['msh_filter_maestro'] ) ? absint( $_POST['msh_filter_maestro'] ) : 0;
    $filters['programa_id'] = isset( $_POST['msh_filter_programa'] ) ? absint( $_POST['msh_filter_programa'] ) : 0;
    $filters['sede_id'] = isset( $_POST['msh_filter_sede'] ) ? absint( $_POST['msh_filter_sede'] ) : 0;
    $filters['rango_id'] = isset( $_POST['msh_filter_rango_edad'] ) ? absint( $_POST['msh_filter_rango_edad'] ) : 0;
    $filters['dia'] = isset( $_POST['msh_filter_dia'] ) ? sanitize_key( $_POST['msh_filter_dia'] ) : '';
    $filters['hora_inicio'] = isset( $_POST['msh_filter_hora_inicio'] ) ? sanitize_text_field( $_POST['msh_filter_hora_inicio'] ) : '';
    if ( $filters['hora_inicio'] && !preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $filters['hora_inicio']) ) {
        $filters['hora_inicio'] = ''; // Invalidar formato incorrecto
    }

    // 3. Llamar a la función de consulta
    $results = msh_perform_complex_schedule_query( $filters );

    // 4. Enviar Respuesta
     wp_send_json_success( array(
         'schedule_data' => $results
     ) );
}
add_action( 'wp_ajax_msh_filter_schedule', 'msh_ajax_filter_schedule_handler' );
add_action( 'wp_ajax_nopriv_msh_filter_schedule', 'msh_ajax_filter_schedule_handler' );

?>