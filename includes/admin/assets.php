<?php
// includes/admin/assets.php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Enqueue scripts y estilos para el área de administración.
 */
function msh_enqueue_admin_assets( $hook_suffix ) {
    global $post_type;
    // Cargar solo en pantallas de edición del CPT 'msh_maestro'
    if ( ( 'post.php' === $hook_suffix || 'post-new.php' === $hook_suffix ) && isset($post_type) && 'msh_maestro' === $post_type ) {
        // Encolar CSS principal
        wp_enqueue_style(
            'msh-admin-styles',
            MSH_PLUGIN_URL . 'assets/css/admin-styles.css',
            array('thickbox'), // *** Añadir dependencia thickbox ***
            MSH_VERSION
        );
        // Encolar JS principal
        wp_enqueue_script(
            'msh-admin-script',
            MSH_PLUGIN_URL . 'assets/js/admin-script.js',
            array( 'jquery', 'thickbox' ), // *** Añadir dependencia thickbox ***
            MSH_VERSION,
            true // Cargar en footer
        );
        // *** Encolar ThickBox explícitamente (buena práctica) ***
        wp_enqueue_script('thickbox');
        wp_enqueue_style('thickbox');
        // Pasar datos de PHP a JS (incluir nuevos textos)
        wp_localize_script( 'msh-admin-script', 'msh_admin_data', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ), // Pasar la URL ajax
            'manage_clases_nonce' => wp_create_nonce( 'msh_manage_clases_action' ), // Nonce para acciones generales de clases
            'save_clase_nonce' => wp_create_nonce( 'msh_save_clase_action' ), // Nonce específico para guardar clase
            // --- Textos para Disponibilidad ---
            'confirm_delete_disponibilidad' => __( '¿Estás seguro de que quieres eliminar este bloque de disponibilidad?', 'music-schedule-manager' ),
            'no_blocks_msg' => esc_js( __( 'Aún no se han añadido bloques de disponibilidad.', 'music-schedule-manager' ) ),
            // --- Textos para Clases Programadas ---
            'modal_title_manage_clase' => __( 'Gestionar Clase Programada', 'music-schedule-manager' ),
            'modal_loading_form' => __( 'Cargando formulario...', 'music-schedule-manager' ),
            'modal_error_loading' => __( 'Error de conexión al cargar el formulario.', 'music-schedule-manager' ),
            'modal_error_saving' => __( 'Error de conexión al guardar. Inténtalo de nuevo.', 'music-schedule-manager' ),
            'modal_error_deleting' => __( 'Error de conexión al eliminar.', 'music-schedule-manager' ),
            'confirm_delete_clase' => __( '¿Estás seguro de que quieres eliminar esta clase permanentemente?', 'music-schedule-manager' ),
            'validation_end_after_start' => __( 'La hora de fin debe ser posterior a la hora de inicio.', 'music-schedule-manager' ),
            'availability_hint_text' => __( 'No disponible o no admisible para este horario/día.', 'music-schedule-manager' ),
            'no_clases_msg' => __('Este maestro no tiene clases programadas.', 'music-schedule-manager')
        ) );
        // Opcional: jQuery UI Sortable para disponibilidad
        // wp_enqueue_script('jquery-ui-sortable');
    }
}
add_action( 'admin_enqueue_scripts', 'msh_enqueue_admin_assets' );
?>