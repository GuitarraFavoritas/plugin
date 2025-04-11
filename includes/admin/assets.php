<?php
// includes/admin/assets.php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Enqueue scripts y estilos para el área de administración.
 */
function msh_enqueue_admin_assets( $hook_suffix ) {
    global $post_type, $post;

    // Cargar solo en pantallas de edición del CPT 'msh_maestro'
    if ( ( 'post.php' === $hook_suffix || 'post-new.php' === $hook_suffix ) && isset($post_type) && 'msh_maestro' === $post_type ) {

        wp_enqueue_style('thickbox');
        wp_enqueue_script('thickbox');
        wp_enqueue_style('msh-admin-styles', MSH_PLUGIN_URL . 'assets/css/admin-styles.css', array('thickbox'), MSH_VERSION);
        wp_enqueue_script('msh-admin-script', MSH_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery', 'thickbox'), MSH_VERSION, true);

        // Obtener datos de disponibilidad inicial SOLO si estamos editando
        $initial_availability_data = array();
        $current_post_id = 0;
        if ( $post && isset($post->ID) ) {
             $current_post_id = $post->ID;
             if ($hook_suffix === 'post.php') {
                 $initial_availability_data = get_post_meta( $current_post_id, '_msh_maestro_disponibilidad', true );
                 $initial_availability_data = is_array( $initial_availability_data ) ? $initial_availability_data : array();
                 // Ordenar usando helper
                 usort($initial_availability_data, 'msh_sort_availability_callback');
             }
        }

        // --- Crear Mapas ID -> Título usando helper ---
        $sede_map = msh_get_cpt_id_title_map('msh_sede');
        $programa_map = msh_get_cpt_id_title_map('msh_programa');
        $rango_map = msh_get_cpt_id_title_map('msh_rango_edad');
        $maestro_map = msh_get_cpt_id_title_map('msh_maestro');

        // Pasar datos de PHP a JS para el *ADMIN*
        wp_localize_script( 'msh-admin-script', 'msh_admin_data', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'post_id' => $current_post_id,
            // Nonces
            'manage_clases_nonce' => wp_create_nonce( 'msh_manage_clases_action' ),
            // *** AÑADIR CAMPO NONCE DE GUARDADO PARA ADMIN JS ***
            'save_clase_nonce_field' => wp_nonce_field( 'msh_save_clase_action', 'msh_save_clase_nonce', true, false ), // Campo nonce para form clase
            'manage_availability_nonce' => wp_create_nonce( 'msh_manage_disponibilidad_action' ),
            'save_availability_nonce' => wp_create_nonce( 'msh_save_disponibilidad_action' ),
            // Datos Disponibilidad
            'availability_initial_data' => $initial_availability_data,
            'sede_names' => $sede_map, 'programa_names' => $programa_map, 'rango_names' => $rango_map, 'maestro_names' => $maestro_map,
            // Textos Disponibilidad
            'text_confirm_delete_availability' => __( '¿Estás seguro...? (Guardar Cambios para confirmar)', 'music-schedule-manager' ),
            'text_no_availability_blocks' => __( 'No hay bloques definidos.', 'music-schedule-manager' ),
            'text_loading_availability_form' => __( 'Cargando formulario...', 'music-schedule-manager' ),
            'text_modal_title_add_availability' => __( 'Añadir Bloque Disponibilidad', 'music-schedule-manager' ),
            'text_modal_title_edit_availability' => __( 'Editar Bloque Disponibilidad', 'music-schedule-manager' ),
            'text_modal_add_button' => __( 'Añadir Bloque', 'music-schedule-manager' ),
            'text_modal_update_button' => __( 'Actualizar Bloque', 'music-schedule-manager' ),
            'text_modal_cancel_button' => __( 'Cancelar', 'music-schedule-manager' ),
            'text_saving_availability' => __( 'Guardando...', 'music-schedule-manager' ),
            'text_availability_saved' => __( 'Disponibilidad guardada.', 'music-schedule-manager' ),
            'text_availability_save_error' => __( 'Error al guardar.', 'music-schedule-manager' ),
            'text_validation_end_after_start' => __( 'Hora fin debe ser posterior a inicio.', 'music-schedule-manager' ),
            'text_validation_duplicate_slot' => __('Horario duplicado (día/inicio).', 'music-schedule-manager'),
            'text_delete_button' => __('Eliminar', 'music-schedule-manager'),
            // Textos Clases
            'modal_title_manage_clase' => __( 'Gestionar Clase Programada', 'music-schedule-manager' ),
            'modal_loading_form' => __( 'Cargando formulario...', 'music-schedule-manager' ),
            'modal_error_loading' => __( 'Error al cargar.', 'music-schedule-manager' ),
            'modal_error_saving' => __( 'Error al guardar.', 'music-schedule-manager' ),
            'modal_error_deleting' => __( 'Error al eliminar.', 'music-schedule-manager' ),
            'confirm_delete_clase' => __( '¿Eliminar esta clase permanentemente?', 'music-schedule-manager' ),
            'availability_hint_text' => __( 'No disponible/admisible.', 'music-schedule-manager' ),
            'no_clases_msg' => __('No hay clases programadas.', 'music-schedule-manager'),
            // Textos Generales
            'days_of_week' => msh_get_dias_semana(),
        ) );
    }
}
add_action( 'admin_enqueue_scripts', 'msh_enqueue_admin_assets' );
?>