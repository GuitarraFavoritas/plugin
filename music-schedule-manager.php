<?php
/**
 * Plugin Name:       Music Schedule Manager
 * Plugin URI:        https://example.com/
 * Description:       Administra horarios de maestros de música, sedes, programas y rangos de edad.
 * Version:           1.1.0
 * Author:            Tu Nombre
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       music-schedule-manager
 * Domain Path:       /languages
 */
// Evitar acceso directo al archivo
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Definir constantes útiles
define( 'MSH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MSH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MSH_VERSION', '1.1.0' );
// Incluir archivos necesarios
require_once MSH_PLUGIN_DIR . 'includes/cpt-registrations.php';
require_once MSH_PLUGIN_DIR . 'includes/admin/assets.php';
require_once MSH_PLUGIN_DIR . 'includes/meta-boxes/maestro-disponibilidad.php';
require_once MSH_PLUGIN_DIR . 'includes/meta-boxes/maestro-clases.php'; // *** AÑADIR ESTA LÍNEA ***
require_once MSH_PLUGIN_DIR . 'includes/frontend/shortcode-viewer.php'; // <-- AÑADIR ESTA LÍNEA
/**
 * Funciones de Activación / Desactivación
 * (Importante: Las funciones de registro de CPT deben estar definidas ANTES de llamar a flush_rewrite_rules)
 */
function msh_plugin_activation() {
    // Asegurarse de que los CPTs estén registrados antes de limpiar las reglas
    msh_registrar_todos_cpts();
    // Añade aquí llamadas a otros registros de CPT si los tienes en cpt-registrations.php
    // msh_register_sede_cpt();
    // msh_register_programa_cpt();
    // msh_register_rango_edad_cpt();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'msh_plugin_activation' );
function msh_plugin_deactivation() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'msh_plugin_deactivation' );
/**
 * Cargar Text Domain para traducciones
 */
function msh_load_textdomain() {
    load_plugin_textdomain( 'music-schedule-manager', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'msh_load_textdomain' );
?>