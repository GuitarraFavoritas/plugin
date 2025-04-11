<?php
// includes/helpers.php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Obtiene un array asociativo con los días de la semana.
 *
 * @return array Array [clave => Nombre Traducido].
 */
if (!function_exists('msh_get_dias_semana')) {
    function msh_get_dias_semana() {
        return array(
            'lunes'     => __( 'Lunes', 'music-schedule-manager' ),
            'martes'    => __( 'Martes', 'music-schedule-manager' ),
            'miercoles' => __( 'Miércoles', 'music-schedule-manager' ),
            'jueves'    => __( 'Jueves', 'music-schedule-manager' ),
            'viernes'   => __( 'Viernes', 'music-schedule-manager' ),
            'sabado'    => __( 'Sábado', 'music-schedule-manager' ),
            'domingo'   => __( 'Domingo', 'music-schedule-manager' ),
        );
    }
}

/**
 * Obtiene un mapa ID -> Título para un CPT específico.
 *
 * @param string $post_type El slug del CPT.
 * @return array Mapa [ID => Título].
 */
if (!function_exists('msh_get_cpt_id_title_map')) {
    function msh_get_cpt_id_title_map( $post_type ) {
        $map = array();
        $posts = get_posts( array(
            'post_type' => $post_type,
            'numberposts' => -1,
            'post_status' => 'publish',
            'fields' => 'ids' // Solo obtener IDs para eficiencia
        ) );
        if ($posts) {
            foreach ($posts as $id) {
                $title = get_the_title($id);
                if ($title) { // Asegurarse de que el título no esté vacío
                  $map[$id] = $title;
                }
            }
        }
        return $map;
    }
}

/**
* Helper: Obtiene opciones HTML para un <select> basado en un CPT.
* Usado en el formulario de filtros del frontend.
*
* @param string $post_type El slug del Custom Post Type.
* @return string HTML de las <option> tags.
*/
if (!function_exists('msh_get_cpt_options_html')) {
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
                 // Añadir comprobación si $p->post_title existe por si acaso
                 if (isset($p->ID) && isset($p->post_title)) {
                    $options_html .= '<option value="' . esc_attr( $p->ID ) . '">' . esc_html( $p->post_title ) . '</option>';
                 }
            }
        }
        return $options_html;
    }
}


/**
 * Función de comparación para ordenar los bloques de disponibilidad
 * por día (L-D) y luego hora de inicio. Usada con usort().
 */
if (!function_exists('msh_sort_availability_callback')) {
    function msh_sort_availability_callback($a, $b) {
        $dias_orden = array_flip(array_keys(msh_get_dias_semana()));
        $dia_a = $a['dia'] ?? ''; $dia_b = $b['dia'] ?? '';
        $orden_dia_a = isset($dias_orden[$dia_a]) ? $dias_orden[$dia_a] : 99;
        $orden_dia_b = isset($dias_orden[$dia_b]) ? $dias_orden[$dia_b] : 99;
        if ($orden_dia_a != $orden_dia_b) return $orden_dia_a - $orden_dia_b;
        $hora_a = $a['hora_inicio'] ?? '99:99'; $hora_b = $b['hora_inicio'] ?? '99:99';
        $time_a = strtotime($hora_a); $time_b = strtotime($hora_b);
        if ($time_a === false && $time_b === false) return 0;
        if ($time_a === false) return 1; if ($time_b === false) return -1;
        return $time_a - $time_b;
    }
}

/**
 * Función de comparación para ordenar las clases por día (L-D) y luego hora de inicio.
 * Usada con usort().
 */
if (!function_exists('msh_sort_clases_callback')) {
    function msh_sort_clases_callback($a, $b) {
        $dias_orden = array_flip(array_keys(msh_get_dias_semana()));
        $dia_a = get_post_meta($a->ID, '_msh_clase_dia', true);
        $dia_b = get_post_meta($b->ID, '_msh_clase_dia', true);
        $orden_dia_a = isset($dias_orden[$dia_a]) ? $dias_orden[$dia_a] : 99;
        $orden_dia_b = isset($dias_orden[$dia_b]) ? $dias_orden[$dia_b] : 99;
        if ($orden_dia_a != $orden_dia_b) return $orden_dia_a - $orden_dia_b;
        $hora_a = get_post_meta($a->ID, '_msh_clase_hora_inicio', true);
        $hora_b = get_post_meta($b->ID, '_msh_clase_hora_inicio', true);
        $time_a = strtotime($hora_a); $time_b = strtotime($hora_b);
        if ($time_a === false && $time_b === false) return 0;
        if ($time_a === false) return 1; if ($time_b === false) return -1;
        return $time_a - $time_b;
    }
}


/**
 * Calcula el tiempo de traslado requerido en minutos basado en la hora del día.
 */
if (!function_exists('msh_get_required_travel_time')) {
    function msh_get_required_travel_time( $timestamp_or_time ): int {
        $hour = null;
        if ( is_numeric($timestamp_or_time) ) { $hour = (int) date('G', $timestamp_or_time); }
        elseif (is_string($timestamp_or_time) && preg_match('/^(\d{1,2}):\d{2}$/', $timestamp_or_time, $matches)) { $hour = (int) $matches[1]; }
        if ($hour === null) return 60;
        return ( $hour >= 16 ) ? 120 : 60;
    }
}

/**
* Sanitiza un array de IDs de post, asegurando que sean enteros válidos,
* pertenezcan al tipo de post correcto y estén publicados.
*/
if (!function_exists('msh_sanitize_post_ids')) {
   function msh_sanitize_post_ids( $ids, string $post_type ): array {
        if ( ! is_array( $ids ) ) { return []; }
        $sanitized_ids = [];
        foreach ( $ids as $id ) {
            $int_id = absint( $id );
            // Comprobar si el post existe y es del tipo correcto antes de llamar a get_post_status
            $post_object = get_post($int_id); // Obtener el objeto post
            if ( $int_id > 0 && $post_object instanceof WP_Post && $post_object->post_type === $post_type && $post_object->post_status === 'publish' ) {
                $sanitized_ids[] = $int_id;
            }
        }
        return array_unique( $sanitized_ids );
    }
}

?>