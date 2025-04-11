<?php
// includes/admin/assets.php

if ( ! defined( 'ABSPATH' ) ) exit;

function msh_enqueue_admin_assets( $hook_suffix ) {
    global $post_type, $post; // Necesitamos $post para obtener el ID del maestro

    if ( ( 'post.php' === $hook_suffix || 'post-new.php' === $hook_suffix ) && isset($post_type) && 'msh_maestro' === $post_type ) {

        // ... (enqueue de estilos y scripts como antes, asegurando thickbox) ...
        wp_enqueue_style('msh-admin-styles', MSH_PLUGIN_URL . 'assets/css/admin-styles.css', array('thickbox'), MSH_VERSION);
        wp_enqueue_script('msh-admin-script', MSH_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery', 'thickbox'), MSH_VERSION, true);
        wp_enqueue_script('thickbox');
        wp_enqueue_style('thickbox');

        // Obtener datos de disponibilidad inicial SOLO si estamos editando un post existente
        $initial_availability_data = array();
        if ( $post && isset($post->ID) && $hook_suffix === 'post.php' ) {
             $initial_availability_data = get_post_meta( $post->ID, '_msh_maestro_disponibilidad', true );
             if ( !is_array( $initial_availability_data ) ) {
                 $initial_availability_data = array();
             }
             // Ordenar los datos iniciales
             usort($initial_availability_data, 'msh_sort_availability_callback');
        }


        // Pasar datos de PHP a JS
        wp_localize_script( 'msh-admin-script', 'msh_admin_data', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'post_id' => $post ? $post->ID : 0, // Pasar el ID del maestro actual

            // --- Nonces ---
            'manage_clases_nonce' => wp_create_nonce( 'msh_manage_clases_action' ),
            'save_clase_nonce' => wp_create_nonce( 'msh_save_clase_action' ),
            'manage_availability_nonce' => wp_create_nonce( 'msh_manage_disponibilidad_action' ), // Nonce para cargar form disponibilidad
            'save_availability_nonce' => wp_create_nonce( 'msh_save_disponibilidad_action' ), // Nonce para guardar TODA la disponibilidad

            // --- Textos Disponibilidad General (MODAL) ---
            'availability_initial_data' => $initial_availability_data, // <<< DATOS INICIALES
            'text_confirm_delete_availability' => __( '¿Estás seguro de que quieres eliminar este bloque de disponibilidad? (Se guardará al hacer clic en "Guardar Cambios")', 'music-schedule-manager' ),
            'text_no_availability_blocks' => __( 'No se han definido bloques de disponibilidad.', 'music-schedule-manager' ),
            'text_loading_availability_form' => __( 'Cargando formulario de disponibilidad...', 'music-schedule-manager' ),
            'text_modal_title_add_availability' => __( 'Añadir Bloque de Disponibilidad', 'music-schedule-manager' ),
            'text_modal_title_edit_availability' => __( 'Editar Bloque de Disponibilidad', 'music-schedule-manager' ),
            'text_modal_add_button' => __( 'Añadir Bloque', 'music-schedule-manager' ),
            'text_modal_update_button' => __( 'Actualizar Bloque', 'music-schedule-manager' ),
            'text_modal_cancel_button' => __( 'Cancelar', 'music-schedule-manager' ),
            'text_saving_availability' => __( 'Guardando...', 'music-schedule-manager' ),
            'text_availability_saved' => __( 'Disponibilidad guardada.', 'music-schedule-manager' ),
            'text_availability_save_error' => __( 'Error al guardar.', 'music-schedule-manager' ),
             'text_validation_end_after_start' => __( 'La hora de fin debe ser posterior a la hora de inicio.', 'music-schedule-manager' ),
             'text_validation_duplicate_slot' => __('Ya existe un bloque con el mismo día y hora de inicio.', 'music-schedule-manager'),

            // --- Textos Clases Programadas (Modal) ---
            'modal_title_manage_clase' => __( 'Gestionar Clase Programada', 'music-schedule-manager' ),
            'modal_loading_form' => __( 'Cargando formulario...', 'music-schedule-manager' ),
            'modal_error_loading' => __( 'Error de conexión al cargar el formulario.', 'music-schedule-manager' ),
            'modal_error_saving' => __( 'Error de conexión al guardar. Inténtalo de nuevo.', 'music-schedule-manager' ),
            'modal_error_deleting' => __( 'Error de conexión al eliminar.', 'music-schedule-manager' ),
            'confirm_delete_clase' => __( '¿Estás seguro de que quieres eliminar esta clase permanentemente?', 'music-schedule-manager' ),
            'availability_hint_text' => __( 'No disponible o no admisible para este horario/día.', 'music-schedule-manager' ),
            'no_clases_msg' => __('Este maestro no tiene clases programadas.', 'music-schedule-manager'),

             // --- Textos Generales ---
             'days_of_week' => msh_get_dias_semana() // Pasar nombres de días para JS
        ) );

        // ... (Opcional: Sortable) ...
    }
}
add_action( 'admin_enqueue_scripts', 'msh_enqueue_admin_assets' );

// --- Asegurarse que msh_get_dias_semana y msh_sort_availability_callback existen ---
if (!function_exists('msh_get_dias_semana')) { /* ... definición ... */ }
if (!function_exists('msh_sort_availability_callback')) { /* ... definición ... */ }

?>