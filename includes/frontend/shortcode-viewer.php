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
 *
 * @param array $atts Atributos del shortcode (no usados por ahora).
 * @return string HTML del visor de horarios.
 */
function msh_render_schedule_viewer_shortcode( $atts ) {
    // Encolar scripts y estilos específicos para el frontend SOLO aquí
    // Esto asegura que solo se carguen cuando el shortcode está en la página.
    wp_enqueue_style(
        'msh-frontend-styles',
        MSH_PLUGIN_URL . 'assets/css/frontend-styles.css',
        array(),
        MSH_VERSION
    );
    wp_enqueue_script(
        'msh-frontend-script',
        MSH_PLUGIN_URL . 'assets/js/frontend-script.js',
        array( 'jquery' ), // Dependencia de jQuery
        MSH_VERSION,
        true // Cargar en el footer
    );

    // Pasar datos necesarios a JavaScript
    wp_localize_script( 'msh-frontend-script', 'msh_frontend_data', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'msh_filter_schedule_nonce' ), // Nonce para la petición AJAX
        'loading_message' => esc_html__( 'Buscando horarios...', 'music-schedule-manager' ),
        'error_message'   => esc_html__( 'Ocurrió un error. Por favor, inténtalo de nuevo.', 'music-schedule-manager' ),
        'no_results_message' => esc_html__( 'No se encontraron horarios que coincidan con tu búsqueda.', 'music-schedule-manager' ),
        // Pasar nombres para la tabla (si decidimos mostrarlos en JS)
        'sede_names' => msh_get_cpt_id_title_map('msh_sede'),
        'programa_names' => msh_get_cpt_id_title_map('msh_programa'),
        'rango_names' => msh_get_cpt_id_title_map('msh_rango_edad'),
        'maestro_names' => msh_get_cpt_id_title_map('msh_maestro'),
        'days_of_week' => function_exists('msh_get_dias_semana') ? msh_get_dias_semana() : [],
    ));

    // Iniciar Output Buffering para capturar el HTML
    ob_start();
    ?>
    <div class="msh-schedule-viewer-wrapper">
        <h2><?php esc_html_e( 'Consultar Disponibilidad de Horarios', 'music-schedule-manager' ); ?></h2>

        <?php echo msh_render_filter_form(); // Mostrar el formulario de filtros ?>

        <div id="msh-schedule-results-wrapper">
            <h3><?php esc_html_e( 'Resultados', 'music-schedule-manager' ); ?></h3>
            <div id="msh-schedule-results-feedback" style="display: none;"></div> <?php // Para mensajes de carga/error/no resultados ?>
            <div id="msh-schedule-results-table-container">
                <?php // La tabla se llenará aquí vía AJAX ?>
                <p id="msh-initial-message"><?php esc_html_e( 'Utiliza los filtros para buscar horarios disponibles.', 'music-schedule-manager' ); ?></p>
            </div>
        </div>
    </div>
    <?php
    // Devolver el HTML capturado
    return ob_get_clean();
}

/**
 * Renderiza el HTML del formulario de filtros.
 *
 * @return string HTML del formulario.
 */
function msh_render_filter_form() {
    ob_start();
    ?>
    <form id="msh-schedule-filters-form">
        <?php wp_nonce_field( 'msh_filter_schedule_nonce', 'msh_filter_nonce_field' ); // Nonce visible para JS (alternativa a localize) ?>

        <div class="msh-filter-row">
            <div class="msh-filter-group">
                <label for="msh-filter-maestro"><?php esc_html_e( 'Maestro:', 'music-schedule-manager' ); ?></label>
                <select name="msh_filter_maestro" id="msh-filter-maestro">
                    <option value=""><?php esc_html_e( 'Todos', 'music-schedule-manager' ); ?></option>
                    <?php echo msh_get_cpt_options_html( 'msh_maestro' ); ?>
                </select>
            </div>
            <div class="msh-filter-group">
                <label for="msh-filter-programa"><?php esc_html_e( 'Programa:', 'music-schedule-manager' ); ?></label>
                <select name="msh_filter_programa" id="msh-filter-programa">
                    <option value=""><?php esc_html_e( 'Todos', 'music-schedule-manager' ); ?></option>
                    <?php echo msh_get_cpt_options_html( 'msh_programa' ); ?>
                </select>
            </div>
             <div class="msh-filter-group">
                <label for="msh-filter-sede"><?php esc_html_e( 'Sede:', 'music-schedule-manager' ); ?></label>
                <select name="msh_filter_sede" id="msh-filter-sede">
                    <option value=""><?php esc_html_e( 'Todas', 'music-schedule-manager' ); ?></option>
                    <?php echo msh_get_cpt_options_html( 'msh_sede' ); ?>
                </select>
            </div>
        </div>

        <div class="msh-filter-row">
             <div class="msh-filter-group">
                <label for="msh-filter-rango_edad"><?php esc_html_e( 'Rango Edad:', 'music-schedule-manager' ); ?></label>
                <select name="msh_filter_rango_edad" id="msh-filter-rango_edad">
                    <option value=""><?php esc_html_e( 'Todos', 'music-schedule-manager' ); ?></option>
                    <?php echo msh_get_cpt_options_html( 'msh_rango_edad' ); ?>
                </select>
            </div>
            <div class="msh-filter-group">
                 <label for="msh-filter-dia"><?php esc_html_e( 'Día:', 'music-schedule-manager' ); ?></label>
                 <select name="msh_filter_dia" id="msh-filter-dia">
                     <option value=""><?php esc_html_e( 'Cualquier Día', 'music-schedule-manager' ); ?></option>
                     <?php
                         $dias = function_exists('msh_get_dias_semana') ? msh_get_dias_semana() : [];
                         foreach ($dias as $key => $label) {
                             echo '<option value="' . esc_attr($key) . '">' . esc_html($label) . '</option>';
                         }
                     ?>
                 </select>
             </div>
             <div class="msh-filter-group">
                <label for="msh-filter-hora_inicio"><?php esc_html_e( 'Desde (Hora):', 'music-schedule-manager' ); ?></label>
                <input type="time" name="msh_filter_hora_inicio" id="msh-filter-hora_inicio" step="1800"> <?php // step 30 mins ?>
            </div>
        </div>

        <div class="msh-filter-actions">
            <button type="submit" class="button msh-filter-submit-btn"><?php esc_html_e( 'Buscar Horarios', 'music-schedule-manager' ); ?></button>
            <button type="reset" class="button msh-filter-reset-btn"><?php esc_html_e( 'Limpiar Filtros', 'music-schedule-manager' ); ?></button>
        </div>
    </form>
    <?php
    return ob_get_clean();
}

/**
 * Helper: Obtiene opciones HTML para un <select> basado en un CPT.
 *
 * @param string $post_type El slug del Custom Post Type.
 * @return string HTML de las <option> tags.
 */
function msh_get_cpt_options_html( $post_type ) {
    $options_html = '';
    $posts = get_posts( array(
        'post_type' => $post_type,
        'numberposts' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC',
    ) );

    if ( $posts ) {
        foreach ( $posts as $p ) {
            $options_html .= '<option value="' . esc_attr( $p->ID ) . '">' . esc_html( $p->post_title ) . '</option>';
        }
    }
    return $options_html;
}

/**
 * Helper: Obtiene un mapa ID -> Título para un CPT.
 * Usado para pasar nombres a JavaScript.
 *
 * @param string $post_type El slug del CPT.
 * @return array Mapa [ID => Título].
 */
function msh_get_cpt_id_title_map( $post_type ) {
    $map = array();
    $posts = get_posts( array(
        'post_type' => $post_type,
        'numberposts' => -1,
        'post_status' => 'publish',
        'fields' => 'id=>parent' // Eficiente para obtener ID y Título
    ) );
    if ($posts) {
        foreach ($posts as $p) {
            $map[$p->ID] = get_the_title($p->ID); // Obtener título de forma segura
        }
    }
    return $map;
}

/**
 * Realiza la consulta compleja para obtener horarios disponibles y asignados.
 *
 * @param array $filters Array asociativo con los filtros aplicados.
 *                       Ej: ['maestro_id' => 1, 'programa_id' => 10, 'sede_id' => 0, ...]
 * @return array Array de resultados formateados para la tabla frontend.
 */
function msh_perform_complex_schedule_query( $filters = array() ) {

    $final_results = array();

    // 1. Obtener Maestros Relevantes
    $maestro_args = array(
        'post_type' => 'msh_maestro',
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids', // Solo necesitamos los IDs
        'orderby' => 'title',
        'order' => 'ASC'
    );
    if ( !empty($filters['maestro_id']) ) {
        $maestro_args['post__in'] = array( $filters['maestro_id'] );
    }
    $maestro_ids = get_posts( $maestro_args );

    if ( empty($maestro_ids) ) {
        return $final_results; // No hay maestros que coincidan
    }

    // Helper para convertir HH:MM a minutos desde medianoche para facilitar comparaciones
    $time_to_minutes = function($time_str) {
        if (empty($time_str) || !preg_match('/^(\d{1,2}):(\d{2})$/', $time_str, $matches)) {
            return false; // Formato inválido
        }
        return intval($matches[1]) * 60 + intval($matches[2]);
    };

    // 2. Iterar por cada Maestro
    foreach ( $maestro_ids as $maestro_id ) {

        // 2a. Obtener Disponibilidad General del Maestro
        $availability_blocks = get_post_meta( $maestro_id, '_msh_maestro_disponibilidad', true );
        if ( !is_array( $availability_blocks ) || empty( $availability_blocks ) ) {
            continue; // Saltar maestro si no tiene disponibilidad definida
        }

        // Filtrar disponibilidad por día si se especificó
        if ( !empty($filters['dia']) ) {
            $availability_blocks = array_filter($availability_blocks, function($block) use ($filters) {
                return isset($block['dia']) && $block['dia'] === $filters['dia'];
            });
        }

        if ( empty($availability_blocks) ) {
            continue; // Saltar maestro si no tiene disponibilidad para ese día
        }

        // 2b. Obtener Clases Programadas del Maestro (filtrando por día si es posible)
        $clase_args = array(
            'post_type' => 'msh_clase',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_msh_clase_maestro_id',
                    'value' => $maestro_id,
                    'compare' => '=',
                ),
                // Añadir filtro de día aquí si se proporcionó
                 (!empty($filters['dia']) ? array(
                    'key' => '_msh_clase_dia',
                    'value' => $filters['dia'],
                    'compare' => '=',
                ) : array()), // Array vacío si no hay filtro de día
            ),
            // Ordenar por hora de inicio para procesar en orden
            'meta_key' => '_msh_clase_hora_inicio',
            'orderby' => 'meta_value',
            'order' => 'ASC',
        );
        // Remover el array vacío si no hubo filtro de día
         if (empty($filters['dia'])) {
             unset($clase_args['meta_query'][1]);
         }

        $scheduled_classes = get_posts( $clase_args );

        // Crear un mapa de clases por día para acceso rápido
        $classes_by_day = array();
        foreach ($scheduled_classes as $clase_post) {
             $clase_meta = get_post_meta($clase_post->ID); // Obtener todos los metas
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
                     // 'inscritos' => 0, // Asumir 0 o implementar lógica si existe
                 );
             }
        }

        // 3. Procesar cada Bloque de Disponibilidad General
        foreach ($availability_blocks as $avail_block) {
             $current_day = $avail_block['dia'] ?? null;
             if (!$current_day) continue; // Saltar bloque inválido

            $avail_start_min = $time_to_minutes($avail_block['hora_inicio'] ?? '');
            $avail_end_min = $time_to_minutes($avail_block['hora_fin'] ?? '');

            if ($avail_start_min === false || $avail_end_min === false || $avail_end_min <= $avail_start_min) {
                continue; // Saltar bloque con horario inválido
            }

            // Obtener clases que se solapan con ESTE bloque de disponibilidad en ESTE día
            $overlapping_classes = array();
            if (isset($classes_by_day[$current_day])) {
                foreach ($classes_by_day[$current_day] as $clase) {
                    $clase_start_min = $time_to_minutes($clase['hora_inicio']);
                    $clase_end_min = $time_to_minutes($clase['hora_fin']);

                    if ($clase_start_min !== false && $clase_end_min !== false &&
                        $clase_start_min < $avail_end_min && $clase_end_min > $avail_start_min) // Solapamiento
                    {
                        $overlapping_classes[] = $clase;
                    }
                }
                // Ordenar clases solapadas por hora de inicio (ya deberían estarlo por la query, pero por si acaso)
                usort($overlapping_classes, function($a, $b) use ($time_to_minutes) {
                    return $time_to_minutes($a['hora_inicio']) - $time_to_minutes($b['hora_inicio']);
                });
            }

            // 4. Calcular Huecos (Slots Vacíos y Asignados)
            $current_pointer_min = $avail_start_min;

            foreach ($overlapping_classes as $clase) {
                $clase_start_min = $time_to_minutes($clase['hora_inicio']);
                $clase_end_min = $time_to_minutes($clase['hora_fin']);

                 // Sanity check (en teoría ya filtrado, pero por si acaso)
                 if ($clase_start_min === false || $clase_end_min === false || $clase_end_min <= $clase_start_min) continue;

                // 4a. ¿Hay hueco ANTES de esta clase?
                if ($clase_start_min > $current_pointer_min) {
                    // Crear slot VACIO
                    $final_results[] = array(
                        'type' => 'vacio',
                        'dia' => $current_day,
                        'hora_inicio' => date('H:i', mktime(0, $current_pointer_min)),
                        'hora_fin' => date('H:i', mktime(0, $clase_start_min)),
                        'maestro_id' => $maestro_id,
                        'programas_admisibles' => $avail_block['programas'] ?? [],
                        'sedes_admisibles' => $avail_block['sedes'] ?? [],
                        'rangos_admisibles' => $avail_block['rangos'] ?? []
                    );
                }

                // 4b. Añadir slot ASIGNADO (la clase)
                 $final_results[] = array(
                     'type' => 'asignado',
                     'dia' => $current_day,
                     'hora_inicio' => $clase['hora_inicio'],
                     'hora_fin' => $clase['hora_fin'],
                     'maestro_id' => $maestro_id,
                     'programa_id' => $clase['programa_id'],
                     'sede_id' => $clase['sede_id'],
                     'rango_id' => $clase['rango_id'],
                     'capacidad' => $clase['capacidad'],
                     'inscritos' => 0, // Placeholder
                     'clase_id' => $clase['id']
                 );

                 // Mover el puntero al final de esta clase
                 $current_pointer_min = $clase_end_min;
            } // Fin loop clases solapadas

            // 4c. ¿Hay hueco DESPUÉS de la última clase (o si no hubo clases)?
            if ($current_pointer_min < $avail_end_min) {
                 // Crear slot VACIO final
                 $final_results[] = array(
                     'type' => 'vacio',
                     'dia' => $current_day,
                     'hora_inicio' => date('H:i', mktime(0, $current_pointer_min)),
                     'hora_fin' => date('H:i', mktime(0, $avail_end_min)),
                     'maestro_id' => $maestro_id,
                     'programas_admisibles' => $avail_block['programas'] ?? [],
                     'sedes_admisibles' => $avail_block['sedes'] ?? [],
                     'rangos_admisibles' => $avail_block['rangos'] ?? []
                 );
            }

        } // Fin loop bloques disponibilidad

    } // Fin loop maestros

    // 5. Aplicar Filtros Finales (Programa, Sede, Rango, Hora Inicio)
    $filtered_results = array_filter($final_results, function($slot) use ($filters, $time_to_minutes) {
        // Filtro de Programa
        if (!empty($filters['programa_id'])) {
            if ($slot['type'] === 'asignado') {
                if ($slot['programa_id'] != $filters['programa_id']) return false;
            } else { // 'vacio'
                if (!in_array($filters['programa_id'], $slot['programas_admisibles'])) return false;
            }
        }
        // Filtro de Sede
         if (!empty($filters['sede_id'])) {
            if ($slot['type'] === 'asignado') {
                if ($slot['sede_id'] != $filters['sede_id']) return false;
            } else { // 'vacio'
                if (!in_array($filters['sede_id'], $slot['sedes_admisibles'])) return false;
            }
        }
        // Filtro de Rango Edad
         if (!empty($filters['rango_id'])) {
            if ($slot['type'] === 'asignado') {
                if ($slot['rango_id'] != $filters['rango_id']) return false;
            } else { // 'vacio'
                if (!in_array($filters['rango_id'], $slot['rangos_admisibles'])) return false;
            }
        }
        // Filtro de Hora Inicio (Mostrar slots que comiencen A PARTIR de la hora indicada)
        if (!empty($filters['hora_inicio'])) {
            $filter_start_min = $time_to_minutes($filters['hora_inicio']);
            $slot_start_min = $time_to_minutes($slot['hora_inicio']);
            if ($filter_start_min !== false && $slot_start_min !== false) {
                if ($slot_start_min < $filter_start_min) return false;
            }
        }

        return true; // Pasa todos los filtros
    });

    // 6. Re-indexar array y devolver
    return array_values($filtered_results);
}

// --- Modificar el AJAX Handler para usar la nueva función ---

/**
 * Manejador AJAX para filtrar los horarios.
 * Recibe los filtros, realiza la consulta (placeholder por ahora) y devuelve resultados.
 */
function msh_ajax_filter_schedule_handler() {
    // 1. Seguridad: Verificar Nonce
    check_ajax_referer( 'msh_filter_schedule_nonce', 'nonce' ); // 'nonce' es el nombre esperado del campo POST/GET

    // 2. Obtener y Sanitizar Filtros (ejemplos)
    $filters = array();
    $filters['maestro_id'] = isset( $_POST['msh_filter_maestro'] ) ? absint( $_POST['msh_filter_maestro'] ) : 0;
    $filters['programa_id'] = isset( $_POST['msh_filter_programa'] ) ? absint( $_POST['msh_filter_programa'] ) : 0;
    $filters['sede_id'] = isset( $_POST['msh_filter_sede'] ) ? absint( $_POST['msh_filter_sede'] ) : 0;
    $filters['rango_id'] = isset( $_POST['msh_filter_rango_edad'] ) ? absint( $_POST['msh_filter_rango_edad'] ) : 0;
    $filters['dia'] = isset( $_POST['msh_filter_dia'] ) ? sanitize_key( $_POST['msh_filter_dia'] ) : '';
    $filters['hora_inicio'] = isset( $_POST['msh_filter_hora_inicio'] ) ? sanitize_text_field( $_POST['msh_filter_hora_inicio'] ) : '';
    // Validar formato hora si se proporciona
    if ( $filters['hora_inicio'] && !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $filters['hora_inicio']) ) {
        $filters['hora_inicio'] = ''; // Invalidar si el formato no es correcto
    }

    // 3. Llamar a la función de consulta compleja
    $results = msh_perform_complex_schedule_query( $filters );


    // 4. Preparar y Enviar Respuesta JSON
    if ( is_wp_error( $results ) ) { // Si la función real devolviera un WP_Error
        wp_send_json_error( array( 'message' => $results->get_error_message() ) );
    } else {
        wp_send_json_success( array(
            'filters_received' => $filters, // Devolver filtros para debug (opcional)
            'schedule_data' => $results // Los datos para la tabla
        ) );
    }
}
add_action( 'wp_ajax_msh_filter_schedule', 'msh_ajax_filter_schedule_handler' ); // Para usuarios logueados
add_action( 'wp_ajax_nopriv_msh_filter_schedule', 'msh_ajax_filter_schedule_handler' ); // Para usuarios NO logueados (ajusta según necesites)

?>