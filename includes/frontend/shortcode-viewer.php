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

    // 3. *** PUNTO CRÍTICO: AQUÍ IRÍA LA CONSULTA COMPLEJA ***
    // Esta función debería:
    //    - Usar los $filters.
    //    - Consultar disponibilidad general y clases programadas.
    //    - Calcular los huecos vacíos.
    //    - Devolver un array estructurado con los resultados.
    // $results = msh_perform_complex_schedule_query( $filters );

    // ---- PLACEHOLDER POR AHORA ----
    // Simular algunos resultados para probar la tabla JS
    $results = array();
    if ($filters['maestro_id'] || $filters['programa_id'] || $filters['sede_id'] || $filters['rango_id'] || $filters['dia'] || $filters['hora_inicio']) {
         // Solo simular resultados si se aplicó algún filtro (para no mostrar todo por defecto)
        $results = array(
            // Ejemplo Horario Asignado
            array(
                'type' => 'asignado', // 'asignado' o 'vacio'
                'dia' => 'lunes',
                'hora_inicio' => '09:00',
                'hora_fin' => '09:45',
                'maestro_id' => 1, // ID del Maestro
                'programa_id' => 10, // ID Programa específico
                'sede_id' => 20, // ID Sede específica
                'rango_id' => 30, // ID Rango específico
                'capacidad' => 5,
                'inscritos' => 3, // Necesitarías esta info para calcular vacantes
                'clase_id' => 101 // ID del post msh_clase
            ),
             // Ejemplo Horario Vacío
            array(
                'type' => 'vacio',
                'dia' => 'lunes',
                'hora_inicio' => '10:00',
                'hora_fin' => '12:00',
                'maestro_id' => 1,
                'programas_admisibles' => array(10, 11), // IDs Programas admisibles
                'sedes_admisibles' => array(20), // IDs Sedes admisibles
                'rangos_admisibles' => array(30, 31) // IDs Rangos admisibles
            ),
            // Otro ejemplo asignado
             array(
                'type' => 'asignado',
                'dia' => 'martes',
                'hora_inicio' => '16:00',
                'hora_fin' => '17:30',
                'maestro_id' => 2,
                'programa_id' => 12,
                'sede_id' => 21,
                'rango_id' => 32,
                'capacidad' => 1,
                'inscritos' => 0,
                'clase_id' => 105
            ),
        );
        // Filtrar resultados simulados (muy básico)
        if ($filters['maestro_id']) $results = array_filter($results, fn($r) => $r['maestro_id'] == $filters['maestro_id']);
        if ($filters['dia']) $results = array_filter($results, fn($r) => $r['dia'] == $filters['dia']);
         // ... añadir filtros básicos para programa, sede, rango si es necesario para la simulación
    }
    // ---- FIN PLACEHOLDER ----


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