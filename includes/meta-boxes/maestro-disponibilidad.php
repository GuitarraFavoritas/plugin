<?php
// includes/meta-boxes/maestro-disponibilidad.php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// --- Función msh_add_disponibilidad_meta_box (sin cambios) ---
/**
 * Registra la Meta Box en la pantalla de edición del CPT 'msh_maestro'.
 */
function msh_add_disponibilidad_meta_box() {
    add_meta_box(
        'msh_maestro_disponibilidad',
        __( 'Disponibilidad del Maestro', 'music-schedule-manager' ),
        'msh_maestro_disponibilidad_metabox_render',
        'msh_maestro',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes_msh_maestro', 'msh_add_disponibilidad_meta_box' );

// --- Función msh_maestro_disponibilidad_metabox_render (sin cambios) ---
/**
 * Renderiza el contenido HTML de la Meta Box de Disponibilidad.
 * @param WP_Post $post El objeto del post actual (Maestro).
 */
function msh_maestro_disponibilidad_metabox_render( $post ) {
    // ... (contenido de la función sin cambios) ...
    wp_nonce_field( 'msh_guardar_disponibilidad_meta', 'msh_disponibilidad_nonce' );
    $disponibilidad_guardada = get_post_meta( $post->ID, '_msh_maestro_disponibilidad', true );
    $disponibilidad_guardada = is_array( $disponibilidad_guardada ) ? $disponibilidad_guardada : array();
    $sedes = get_posts( array( /* ... */ 'post_type' => 'msh_sede', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) );
    $programas = get_posts( array( /* ... */ 'post_type' => 'msh_programa', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) );
    $rangos = get_posts( array( /* ... */ 'post_type' => 'msh_rango_edad', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) );
     $dias_semana = array( /* ... */
        'lunes'     => __( 'Lunes', 'music-schedule-manager' ),
        'martes'    => __( 'Martes', 'music-schedule-manager' ),
        'miercoles' => __( 'Miércoles', 'music-schedule-manager' ),
        'jueves'    => __( 'Jueves', 'music-schedule-manager' ),
        'viernes'   => __( 'Viernes', 'music-schedule-manager' ),
        'sabado'    => __( 'Sábado', 'music-schedule-manager' ),
        'domingo'   => __( 'Domingo', 'music-schedule-manager' ),
    );
    ?>
    <div id="msh-disponibilidad-container">
        <p><?php esc_html_e( 'Define los bloques de tiempo en los que este maestro está disponible. Los bloques con el mismo día y hora de inicio serán ignorados.', 'music-schedule-manager' ); ?></p>
        <div id="msh-bloques-disponibilidad" class="msh-sortable">
            <?php if ( ! empty( $disponibilidad_guardada ) ) : ?>
                <?php foreach ( $disponibilidad_guardada as $index => $bloque ) : ?>
                    <?php include MSH_PLUGIN_DIR . 'includes/templates/admin-disponibilidad-row.php'; ?>
                <?php endforeach; ?>
            <?php else : ?>
                <p id="msh-no-bloques"><?php esc_html_e( 'Aún no se han añadido bloques de disponibilidad.', 'music-schedule-manager' ); ?></p>
            <?php endif; ?>
        </div>
        <button type="button" id="msh-add-bloque" class="button">
             <span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e( 'Añadir Bloque', 'music-schedule-manager' ); ?>
        </button>
        <div id="msh-bloque-plantilla" style="display: none;">
            <?php
            $index = '{{INDEX}}';
            $bloque = array();
            include MSH_PLUGIN_DIR . 'includes/templates/admin-disponibilidad-row.php';
            ?>
        </div>
    </div>
    <?php
}

// --- Función msh_save_maestro_disponibilidad_meta (MODIFICADA) ---
/**
 * Guarda los datos de la Meta Box de Disponibilidad (CON CHEQUEO DE DUPLICADOS Y AVISO).
 * @param int $post_id ID del post que se está guardando.
 */
function msh_save_maestro_disponibilidad_meta( $post_id ) {
    // 1. Verificaciones básicas
    if ( ! isset( $_POST['msh_disponibilidad_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['msh_disponibilidad_nonce'] ), 'msh_guardar_disponibilidad_meta' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( wp_is_post_revision( $post_id ) ) return;
    // Asegúrate de que el CPT es el correcto antes de verificar permisos
    if ( get_post_type($post_id) !== 'msh_maestro' ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;
    // 2. Comprobar si se enviaron datos
    if ( ! isset( $_POST['msh_disponibilidad'] ) || ! is_array( $_POST['msh_disponibilidad'] ) ) {
        delete_post_meta( $post_id, '_msh_maestro_disponibilidad' );
        // Borrar también el transient de aviso por si acaso
        delete_transient( 'msh_duplicate_notice_' . get_current_user_id() . '_' . $post_id );
        return;
    }
    // 3. Sanitizar y preparar datos
    $disponibilidad_sanitizada = array();
    $dias_permitidos = array( 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo' );
    $bloques_enviados = array_values( $_POST['msh_disponibilidad'] );
    $slots_unicos = array();
    $duplicates_found = false; // <--- Bandera para rastrear duplicados
    foreach ( $bloques_enviados as $bloque ) {
        // ... (resto de la sanitización como antes) ...
        if (!is_array($bloque)) continue;
        $bloque_sanitizado = array();
        $dia_sanitizado = '';
        $hora_inicio_sanitizada = '';
        // Sanitizar día
        if ( isset( $bloque['dia'] ) && in_array( $bloque['dia'], $dias_permitidos, true ) ) {
            $dia_sanitizado = sanitize_key( $bloque['dia'] );
            $bloque_sanitizado['dia'] = $dia_sanitizado;
        } else { continue; }
        // Sanitizar Hora Inicio
        if ( isset( $bloque['hora_inicio'] ) ) {
             $hora_temp = sanitize_text_field( wp_strip_all_tags( $bloque['hora_inicio'] ) );
             if (preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $hora_temp)) {
                 $hora_inicio_sanitizada = $hora_temp;
                 $bloque_sanitizado['hora_inicio'] = $hora_inicio_sanitizada;
             } else { continue; }
        } else { continue; }
        // *** Chequeo de Duplicados ***
        $slot_key = $dia_sanitizado . '-' . $hora_inicio_sanitizada;
        if ( isset( $slots_unicos[ $slot_key ] ) ) {
            $duplicates_found = true; // <--- Marcar que encontramos un duplicado
            continue; // Saltar este bloque
        }
        $slots_unicos[ $slot_key ] = true;
        // *** Fin Chequeo ***
        // ... (resto de la sanitización para hora_fin, sedes, programas, rangos) ...
         // Sanitizar Hora Fin
        $hora_fin_sanitizada = '';
        if ( isset( $bloque['hora_fin'] ) ) {
            $hora_temp = sanitize_text_field( wp_strip_all_tags( $bloque['hora_fin'] ) );
             if (preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $hora_temp)) {
                if (strtotime($hora_temp) > strtotime($hora_inicio_sanitizada)) {
                     $hora_fin_sanitizada = $hora_temp;
                     $bloque_sanitizado['hora_fin'] = $hora_fin_sanitizada;
                } else {
                     $bloque_sanitizado['hora_fin'] = '';
                }
            } else { $bloque_sanitizado['hora_fin'] = ''; }
        } else { $bloque_sanitizado['hora_fin'] = ''; }
        // Sanitizar Sedes, Programas, Rangos (IDs)
        $bloque_sanitizado['sedes'] = msh_sanitize_post_ids( $bloque['sedes'] ?? [], 'msh_sede' );
        $bloque_sanitizado['programas'] = msh_sanitize_post_ids( $bloque['programas'] ?? [], 'msh_programa' );
        $bloque_sanitizado['rangos'] = msh_sanitize_post_ids( $bloque['rangos'] ?? [], 'msh_rango_edad' );

        $disponibilidad_sanitizada[] = $bloque_sanitizado;
    } // Fin foreach
    // 4. Guardar o borrar el meta
    if ( ! empty( $disponibilidad_sanitizada ) ) {
        update_post_meta( $post_id, '_msh_maestro_disponibilidad', $disponibilidad_sanitizada );
    } else {
        delete_post_meta( $post_id, '_msh_maestro_disponibilidad' );
    }
    // 5. Guardar el transient si se encontraron duplicados
    $user_id = get_current_user_id();
    $transient_key = 'msh_duplicate_notice_' . $user_id . '_' . $post_id;
    if ( $duplicates_found ) {
        // Guardar el transient por 60 segundos. El valor puede ser simple (ej. 1)
        set_transient( $transient_key, true, 60 );
    } else {
        // Si no hubo duplicados esta vez, asegurarse de borrar cualquier transient viejo
        delete_transient( $transient_key );
    }
}
add_action( 'save_post_msh_maestro', 'msh_save_maestro_disponibilidad_meta' );

// --- NUEVA FUNCIÓN: msh_sanitize_post_ids (Función auxiliar de sanitización) ---
/**
 * Sanitiza un array de IDs de post, asegurando que sean enteros válidos,
 * pertenezcan al tipo de post correcto y estén publicados.
 *
 * @param mixed $ids El array (o valor) a sanitizar.
 * @param string $post_type El tipo de post esperado.
 * @return array Array de IDs de post válidos y únicos.
 */
function msh_sanitize_post_ids( $ids, string $post_type ): array {
    if ( ! is_array( $ids ) ) {
        return [];
    }
    $sanitized_ids = [];
    foreach ( $ids as $id ) {
        $int_id = absint( $id );
        if ( $int_id > 0 && get_post_type( $int_id ) === $post_type && get_post_status( $int_id ) === 'publish' ) {
            $sanitized_ids[] = $int_id;
        }
    }
    return array_unique( $sanitized_ids );
}

// --- NUEVA FUNCIÓN: msh_display_admin_notices ---
/**
 * Muestra notificaciones en el área de administración.
 * Específicamente, muestra un aviso si se detectaron horarios duplicados al guardar.
 */
function msh_display_admin_notices() {
    // Solo mostrar en la pantalla de edición de posts
    $screen = get_current_screen();
    if ( ! $screen || $screen->id !== 'msh_maestro' || $screen->base !== 'post' ) {
        return;
    }
    // Obtener el ID del post actual que se está editando
    if ( ! isset( $_GET['post'] ) ) {
        return; // No estamos editando un post existente
    }
    $post_id = absint( $_GET['post'] );
    if ( ! $post_id ) {
        return;
    }
    $user_id = get_current_user_id();
    $transient_key = 'msh_duplicate_notice_' . $user_id . '_' . $post_id;
    // Comprobar si el transient existe para este usuario y post
    if ( get_transient( $transient_key ) ) {
        ?>
        <div class="notice notice-warning is-dismissible msh-admin-notice">
            <p>
                <?php esc_html_e( 'Atención: Uno o más bloques de horario no se guardaron porque ya existía una entrada con el mismo día y hora de inicio.', 'music-schedule-manager' ); ?>
            </p>
        </div>
        <?php
        // Eliminar el transient para que no se muestre de nuevo
        delete_transient( $transient_key );
    }
}
add_action( 'admin_notices', 'msh_display_admin_notices' );
?>