<?php
// includes/meta-boxes/maestro-clases.php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registra la Meta Box para mostrar y gestionar las clases programadas del maestro.
 * (Se muestra en la pantalla de edición del CPT 'msh_maestro')
 */
function msh_add_maestro_clases_meta_box() {
    add_meta_box(
        'msh_maestro_clases_asignadas',                       // ID único de la Meta Box
        __( 'Clases Programadas (Horarios Asignados)', 'music-schedule-manager' ), // Título
        'msh_maestro_clases_metabox_render',                  // Función callback para renderizar el HTML
        'msh_maestro',                                        // Slug del CPT donde se mostrará
        'normal',                                             // Contexto (normal, side, advanced)
        'high'                                                // Prioridad (high, core, default, low)
    );
}
add_action( 'add_meta_boxes_msh_maestro', 'msh_add_maestro_clases_meta_box' );

/**
 * Renderiza el contenido de la Meta Box de Clases Programadas (Tabla Admin + Botón Añadir).
 *
 * @param WP_Post $post El objeto del post actual (Maestro).
 */
function msh_maestro_clases_metabox_render( $post ) {
    $maestro_id = $post->ID;

    // Nonce general para acciones en esta meta box (borrar, cargar modal desde admin)
    // Necesario para que admin-script.js lo recoja
    wp_nonce_field( 'msh_manage_clases_action', 'msh_manage_clases_nonce' );

    // Obtener clases de este maestro
    $args = array(
        'post_type' => 'msh_clase',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => array(
             array(
                'key' => '_msh_clase_maestro_id',
                'value' => $maestro_id,
                'compare' => '=',
                'type' => 'NUMERIC'
            )
        )
        // Ordenar con PHP después
    );
    $clases_query = new WP_Query( $args );

    // Obtener mapas de nombres usando el helper
    $sede_names_map = msh_get_cpt_id_title_map('msh_sede');
    $programa_names_map = msh_get_cpt_id_title_map('msh_programa');
    $rango_names_map = msh_get_cpt_id_title_map('msh_rango_edad');
    $dias_semana_disp = msh_get_dias_semana();

    ?>
    <div class="msh-clases-container"> <?php // Contenedor principal para JS del admin ?>
        <div class="msh-clases-table-wrapper">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Día', 'music-schedule-manager'); ?></th>
                        <th><?php esc_html_e('Horario', 'music-schedule-manager'); ?></th>
                        <th><?php esc_html_e('Programa', 'music-schedule-manager'); ?></th>
                        <th><?php esc_html_e('Rango Edad', 'music-schedule-manager'); ?></th>
                        <th><?php esc_html_e('Sede', 'music-schedule-manager'); ?></th>
                        <th><?php esc_html_e('Capacidad', 'music-schedule-manager'); ?></th>
                        <th style="width: 100px;"><?php esc_html_e('Acciones', 'music-schedule-manager'); ?></th>
                    </tr>
                </thead>
                <tbody id="msh-clases-list"> <?php // ID para admin-script.js ?>
                    <?php if ( $clases_query->have_posts() ) : ?>
                        <?php
                        $clases_posts = $clases_query->posts;
                        usort($clases_posts, 'msh_sort_clases_callback'); // Ordenar usando helper

                        foreach ( $clases_posts as $clase_post ) :
                            $clase_id = $clase_post->ID;
                            // Obtener metas individuales para más claridad
                            $dia = get_post_meta( $clase_id, '_msh_clase_dia', true );
                            $hora_inicio = get_post_meta( $clase_id, '_msh_clase_hora_inicio', true );
                            $hora_fin = get_post_meta( $clase_id, '_msh_clase_hora_fin', true );
                            $programa_id = absint(get_post_meta( $clase_id, '_msh_clase_programa_id', true ));
                            $rango_id = absint(get_post_meta( $clase_id, '_msh_clase_rango_id', true ));
                            $sede_id = absint(get_post_meta( $clase_id, '_msh_clase_sede_id', true ));
                            $capacidad = absint(get_post_meta( $clase_id, '_msh_clase_capacidad', true ) ?: 1);

                            // Obtener nombres usando los mapas
                            $programa_title = $programa_names_map[$programa_id] ?? 'N/A';
                            $rango_title = $rango_names_map[$rango_id] ?? 'N/A';
                            $sede_title = $sede_names_map[$sede_id] ?? 'N/A';
                            $dia_display = $dias_semana_disp[$dia] ?? ucfirst($dia);
                            $horario_display = esc_html( $hora_inicio . ' - ' . $hora_fin );
                            ?>
                            <tr id="msh-clase-row-<?php echo esc_attr( $clase_id ); ?>">
                                <td><?php echo esc_html( $dia_display ); ?></td>
                                <td><?php echo $horario_display; ?></td>
                                <td><?php echo esc_html( $programa_title ); ?></td>
                                <td><?php echo esc_html( $rango_title ); ?></td>
                                <td><?php echo esc_html( $sede_title ); ?></td>
                                <td><?php echo esc_html( $capacidad ); ?></td>
                                <td>
                                    <?php // Botones para admin-script.js ?>
                                    <button type="button" class="button button-small msh-edit-clase" data-clase-id="<?php echo esc_attr( $clase_id ); ?>"><?php esc_html_e('Editar'); ?></button>
                                    <button type="button" class="button button-small button-link-delete msh-delete-clase" data-clase-id="<?php echo esc_attr( $clase_id ); ?>"><?php esc_html_e('Eliminar'); ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                         <?php wp_reset_postdata(); ?>
                    <?php else : ?>
                        <tr id="msh-no-clases-row">
                            <td colspan="7"><?php esc_html_e('Este maestro no tiene clases programadas.', 'music-schedule-manager'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <p>
             <?php // Botón para admin-script.js ?>
            <button type="button" id="msh-add-new-clase-btn" class="button button-primary" data-maestro-id="<?php echo esc_attr( $maestro_id ); ?>">
                <span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e('Asignar Nuevo Horario', 'music-schedule-manager'); ?>
            </button>
        </p>
    </div>

    <?php // Contenedor Modal para Admin (reutilizado por JS) ?>
    <div id="msh-clase-modal-container" style="display: none;">
         <div id="msh-clase-modal-content">
             <p><?php esc_html_e('Cargando formulario...', 'music-schedule-manager'); ?></p>
         </div>
    </div>
    <?php
}


// =========================================================================
// =                         MANEJADORES AJAX                            =
// =========================================================================
// Estos handlers son usados por admin-script.js Y frontend-script.js

/**
 * Manejador AJAX para cargar el formulario de Añadir/Editar Clase.
 * Devuelve el HTML del formulario y la disponibilidad del maestro.
 */
function msh_ajax_load_clase_form_handler() {
    // 1. Seguridad: Nonce y Permisos (IMPORTANTE)
    check_ajax_referer( 'msh_manage_clases_action', 'security' );

    // *** VERIFICAR LOGIN ***
    if ( ! is_user_logged_in() ) {
        error_log("MSH AJAX Load Form - User not logged in. Sending login_required error."); // LOG
        wp_send_json_error( array(
            'message' => __( 'Debes iniciar sesión para realizar esta acción.', 'music-schedule-manager' ),
            'login_required' => true, // Flag para JS
            'login_url' => wp_login_url( $_SERVER['REQUEST_URI'] ?? get_permalink() ) // URL de login
        ) );
        wp_die(); // Terminar ejecución explícitamente
    }
    // *** FIN VERIFICAR LOGIN ***

    // Definir la capacidad mínima requerida para ver/usar el formulario
    $required_capability = apply_filters('msh_capability_manage_clases', 'edit_posts'); // Permite filtrar la capacidad
    if ( ! current_user_can( $required_capability ) ) {
        wp_send_json_error( array( 'message' => __( 'Permiso denegado.', 'music-schedule-manager' ) ) );
    }

    // 2. Obtener IDs
    $maestro_id = isset( $_POST['maestro_id'] ) ? absint( $_POST['maestro_id'] ) : 0;
    $clase_id   = isset( $_POST['clase_id'] ) ? absint( $_POST['clase_id'] ) : 0;

    // 3. Validar Maestro ID
    if ( ! $maestro_id || get_post_type( $maestro_id ) !== 'msh_maestro' ) {
         wp_send_json_error( array( 'message' => __( 'ID de Maestro inválido.', 'music-schedule-manager' ) ) );
    }

    // 4. Cargar datos si es edición
    $clase_data = array();
    if ( $clase_id > 0 ) {
        $clase_post = get_post( $clase_id );
        if ( ! $clase_post || $clase_post->post_type !== 'msh_clase' ) {
             wp_send_json_error( array( 'message' => __( 'ID de Clase inválido.', 'music-schedule-manager' ) ) );
        }
        $clase_maestro_id_meta = get_post_meta( $clase_id, '_msh_clase_maestro_id', true );
        if ( absint( $clase_maestro_id_meta ) !== $maestro_id ) {
             wp_send_json_error( array( 'message' => __( 'Clase no pertenece al maestro especificado.', 'music-schedule-manager' ) ) );
        }
        // Cargar todos los metas de la clase para pre-rellenar
        $clase_data['dia'] = get_post_meta( $clase_id, '_msh_clase_dia', true );
        $clase_data['hora_inicio'] = get_post_meta( $clase_id, '_msh_clase_hora_inicio', true );
        $clase_data['hora_fin'] = get_post_meta( $clase_id, '_msh_clase_hora_fin', true );
        $clase_data['sede_id'] = absint(get_post_meta( $clase_id, '_msh_clase_sede_id', true ));
        $clase_data['programa_id'] = absint(get_post_meta( $clase_id, '_msh_clase_programa_id', true ));
        $clase_data['rango_id'] = absint(get_post_meta( $clase_id, '_msh_clase_rango_id', true ));
        $clase_data['capacidad'] = absint(get_post_meta( $clase_id, '_msh_clase_capacidad', true ) ?: 1);
    }

    // 5. Obtener disponibilidad del maestro (para filtros JS)
    $maestro_disponibilidad = get_post_meta( $maestro_id, '_msh_maestro_disponibilidad', true );
    $maestro_disponibilidad = is_array( $maestro_disponibilidad ) ? $maestro_disponibilidad : array();

    // 6. Obtener listas para selects del formulario
    $sedes = get_posts( array( 'post_type'=>'msh_sede', 'numberposts'=>-1, 'post_status'=>'publish', 'orderby'=>'title', 'order'=>'ASC' ) );
    $programas = get_posts( array( 'post_type'=>'msh_programa', 'numberposts'=>-1, 'post_status'=>'publish', 'orderby'=>'title', 'order'=>'ASC' ) );
    $rangos = get_posts( array( 'post_type'=>'msh_rango_edad', 'numberposts'=>-1, 'post_status'=>'publish', 'orderby'=>'title', 'order'=>'ASC' ) );
    $dias_semana = msh_get_dias_semana();

    // 7. Generar HTML del formulario (Output Buffering)
    ob_start();
    ?>
    <form id="msh-clase-form" class="msh-modal-form"> <?php // Añadir clase genérica de modal form ?>
        <input type="hidden" name="maestro_id" value="<?php echo esc_attr( $maestro_id ); ?>">
        <input type="hidden" name="clase_id" value="<?php echo esc_attr( $clase_id ); ?>">
        <?php // El campo nonce de guardado se inyecta vía JS desde msh_frontend_data / msh_admin_data ?>

        <h2><?php echo $clase_id > 0 ? esc_html__( 'Editar Clase Programada', 'music-schedule-manager' ) : esc_html__( 'Asignar Nuevo Horario', 'music-schedule-manager' ); ?></h2>

        <table class="form-table"><tbody>
            <tr class="form-field"><th><label for="msh_clase_dia"><?php esc_html_e('Día'); ?> <span class="description">(req.)</span></label></th><td><select name="msh_clase_dia" id="msh_clase_dia" required><option value=""><?php esc_html_e('-- Seleccionar --'); ?></option><?php foreach ($dias_semana as $k => $l): ?><option value="<?php echo esc_attr($k); ?>" <?php selected($clase_data['dia'] ?? '', $k); ?>><?php echo esc_html($l); ?></option><?php endforeach; ?></select></td></tr>
            <tr class="form-field"><th><label for="msh_clase_hora_inicio"><?php esc_html_e('Hora Inicio'); ?> <span class="description">(req.)</span></label></th><td><input type="time" name="msh_clase_hora_inicio" id="msh_clase_hora_inicio" value="<?php echo esc_attr($clase_data['hora_inicio'] ?? ''); ?>" required></td></tr>
            <tr class="form-field"><th><label for="msh_clase_hora_fin"><?php esc_html_e('Hora Fin'); ?> <span class="description">(req.)</span></label></th><td><input type="time" name="msh_clase_hora_fin" id="msh_clase_hora_fin" value="<?php echo esc_attr($clase_data['hora_fin'] ?? ''); ?>" required></td></tr>
            <tr class="form-field"><th><label for="msh_clase_sede_id"><?php esc_html_e('Sede'); ?> <span class="description">(req.)</span></label></th><td><select name="msh_clase_sede_id" id="msh_clase_sede_id" required><option value=""><?php esc_html_e('-- Seleccionar --'); ?></option><?php foreach ($sedes as $s): ?><option value="<?php echo esc_attr($s->ID); ?>" <?php selected($clase_data['sede_id'] ?? '', $s->ID); ?> data-admisible="false"><?php echo esc_html($s->post_title); ?></option><?php endforeach; ?></select><p class="description msh-availability-hint" style="display:none; color: red;"><?php esc_html_e('No disponible/admisible.'); ?></p></td></tr>
            <tr class="form-field"><th><label for="msh_clase_programa_id"><?php esc_html_e('Programa'); ?> <span class="description">(req.)</span></label></th><td><select name="msh_clase_programa_id" id="msh_clase_programa_id" required><option value=""><?php esc_html_e('-- Seleccionar --'); ?></option><?php foreach ($programas as $p): ?><option value="<?php echo esc_attr($p->ID); ?>" <?php selected($clase_data['programa_id'] ?? '', $p->ID); ?> data-admisible="false"><?php echo esc_html($p->post_title); ?></option><?php endforeach; ?></select><p class="description msh-availability-hint" style="display:none; color: red;"><?php esc_html_e('No admisible.'); ?></p></td></tr>
            <tr class="form-field"><th><label for="msh_clase_rango_id"><?php esc_html_e('Rango Edad'); ?> <span class="description">(req.)</span></label></th><td><select name="msh_clase_rango_id" id="msh_clase_rango_id" required><option value=""><?php esc_html_e('-- Seleccionar --'); ?></option><?php foreach ($rangos as $r): ?><option value="<?php echo esc_attr($r->ID); ?>" <?php selected($clase_data['rango_id'] ?? '', $r->ID); ?> data-admisible="false"><?php echo esc_html($r->post_title); ?></option><?php endforeach; ?></select><p class="description msh-availability-hint" style="display:none; color: red;"><?php esc_html_e('No admisible.'); ?></p></td></tr>
            <tr class="form-field"><th><label for="msh_clase_capacidad"><?php esc_html_e('Capacidad Máx.'); ?> <span class="description">(req.)</span></label></th><td><input type="number" name="msh_clase_capacidad" id="msh_clase_capacidad" value="<?php echo esc_attr($clase_data['capacidad'] ?? '1'); ?>" min="1" step="1" required style="width: 80px;"></td></tr>
        </tbody></table>
        <div id="msh-clase-validation-messages" style="color: red; margin-bottom: 10px; display: none;"></div>
        <div id="msh-clase-proximity-warning" style="color: orange; margin-bottom: 10px; display: none;"></div>
        <p class="submit">
            <button type="submit" class="button button-primary" id="msh-save-clase-btn"><?php echo $clase_id > 0 ? esc_html__( 'Actualizar Clase', 'music-schedule-manager' ) : esc_html__( 'Guardar Clase', 'music-schedule-manager' ); ?></button>
            <button type="button" class="button button-secondary msh-cancel-clase-btn"><?php esc_html_e('Cancelar', 'music-schedule-manager'); ?></button>
            <span class="spinner"></span>
        </p>
    </form>
    <?php
    $form_html = ob_get_clean();

    // 8. Enviar respuesta JSON
    wp_send_json_success( array(
        'html' => $form_html,
        'maestro_availability' => $maestro_disponibilidad // Pasar disponibilidad para filtros JS
    ) );
}
add_action( 'wp_ajax_msh_load_clase_form', 'msh_ajax_load_clase_form_handler' );
// add_action( 'wp_ajax_nopriv_msh_load_clase_form', 'msh_ajax_load_clase_form_handler' ); // Considerar seguridad

// *** AÑADIR HOOK NOPRIV PARA QUE EL HANDLER SE EJECUTE ***
add_action( 'wp_ajax_nopriv_msh_load_clase_form', 'msh_ajax_load_clase_form_handler' );


/**
 * Manejador AJAX para guardar (crear/actualizar) una Clase Programada.
 */
function msh_ajax_save_clase_handler() {
    error_log("--- MSH Save Clase AJAX Start ---"); // LOG INICIO HANDLER

    // 1. Seguridad: Nonce y Permisos
    check_ajax_referer( 'msh_save_clase_action', 'security' );
    $required_capability_save = apply_filters('msh_capability_save_clases', 'publish_posts');
    if ( ! current_user_can( $required_capability_save ) ) {
        error_log("MSH Save Clase - ERROR: Permiso denegado.");
        wp_send_json_error( array( 'message' => __( 'No tienes permiso para guardar clases.', 'music-schedule-manager' ) ) );
    }
    error_log("MSH Save Clase - Permisos OK.");

    // 2. Obtener y Sanitizar Datos
    $clase_id   = isset( $_POST['clase_id'] ) ? absint( $_POST['clase_id'] ) : 0;
    $maestro_id = isset( $_POST['maestro_id'] ) ? absint( $_POST['maestro_id'] ) : 0;
    $dia        = isset( $_POST['msh_clase_dia'] ) ? sanitize_key( $_POST['msh_clase_dia'] ) : '';
    $hora_inicio= isset( $_POST['msh_clase_hora_inicio'] ) ? sanitize_text_field( wp_strip_all_tags( $_POST['msh_clase_hora_inicio'] ) ) : '';
    $hora_fin   = isset( $_POST['msh_clase_hora_fin'] ) ? sanitize_text_field( wp_strip_all_tags( $_POST['msh_clase_hora_fin'] ) ) : '';
    $sede_id    = isset( $_POST['msh_clase_sede_id'] ) ? absint( $_POST['msh_clase_sede_id'] ) : 0;
    $programa_id= isset( $_POST['msh_clase_programa_id'] ) ? absint( $_POST['msh_clase_programa_id'] ) : 0;
    $rango_id   = isset( $_POST['msh_clase_rango_id'] ) ? absint( $_POST['msh_clase_rango_id'] ) : 0;
    $capacidad  = isset( $_POST['msh_clase_capacidad'] ) ? absint( $_POST['msh_clase_capacidad'] ) : 1;
    if ($capacidad < 1) $capacidad = 1;

    error_log("MSH Save Clase - Datos Recibidos: " . print_r(array( // LOG DATOS RECIBIDOS
        'clase_id' => $clase_id, 'maestro_id' => $maestro_id, 'dia' => $dia, 'inicio' => $hora_inicio, 'fin' => $hora_fin,
        'sede' => $sede_id, 'programa' => $programa_id, 'rango' => $rango_id, 'capacidad' => $capacidad
    ), true));
    // Log del $_POST completo (puede ser útil pero largo)
    // error_log("MSH Save Clase - Raw POST data: " . print_r($_POST, true));

    // 3. Validaciones Cruciales
    $errors = array();
    $dias_permitidos = array_keys( msh_get_dias_semana() );
    // ... (Validaciones básicas como antes: maestro, día, horas, IDs > 0) ...
    if ( empty($maestro_id) || get_post_type($maestro_id) !== 'msh_maestro' ) $errors[] = __('ID Maestro inválido.');
    if ( empty($dia) || !in_array($dia, $dias_permitidos) ) $errors[] = __('Día inválido.');
    if ( empty($hora_inicio) || !preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $hora_inicio) ) $errors[] = __('Hora inicio inválida.');
    if ( empty($hora_fin) || !preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $hora_fin) ) $errors[] = __('Hora fin inválida.');
    if ( !$errors && strtotime($hora_fin) <= strtotime($hora_inicio) ) $errors[] = __('Hora fin debe ser posterior a inicio.');
    if ( empty($sede_id) || get_post_type($sede_id) !== 'msh_sede' ) $errors[] = __('Sede inválida.');
    if ( empty($programa_id) || get_post_type($programa_id) !== 'msh_programa' ) $errors[] = __('Programa inválido.');
    if ( empty($rango_id) || get_post_type($rango_id) !== 'msh_rango_edad' ) $errors[] = __('Rango edad inválido.');

    if (!empty($errors)) {
        error_log("MSH Save Clase - ERROR: Validación Básica Falló: " . implode(', ', $errors));
        wp_send_json_error( array( 'message' => implode('<br>', $errors) ) );
    }

    // ---> INICIO DEBUG VALIDACIÓN DISPONIBILIDAD <---
    error_log("MSH Save Clase - Iniciando Validación Disponibilidad/Admisibilidad...");
    $maestro_disponibilidad = get_post_meta( $maestro_id, '_msh_maestro_disponibilidad', true );
    $maestro_disponibilidad = is_array($maestro_disponibilidad) ? $maestro_disponibilidad : [];
    error_log("MSH Save Clase - Disponibilidad General Maestro $maestro_id: " . print_r($maestro_disponibilidad, true)); // LOG DISPONIBILIDAD

    $is_within_availability = false;
    $is_admissible = false;
    $checked_block = null; // Para saber en qué bloque debería encajar

    // Convertir horas de la clase propuesta a minutos para comparar
    $time_to_minutes = function($time_str){ if(empty($time_str) || !preg_match('/^(\d{1,2}):(\d{2})$/', $time_str, $m)) return false; return intval($m[1])*60+intval($m[2]); };
    $clase_start_min_prop = $time_to_minutes($hora_inicio);
    $clase_end_min_prop = $time_to_minutes($hora_fin);
    error_log("MSH Save Clase - Hora propuesta (mins): $clase_start_min_prop - $clase_end_min_prop");

    if ($clase_start_min_prop !== false && $clase_end_min_prop !== false) {
        foreach ( $maestro_disponibilidad as $index => $b ) {
            $block_dia = $b['dia'] ?? '';
            $block_start_min = $time_to_minutes($b['hora_inicio'] ?? '');
            $block_end_min = $time_to_minutes($b['hora_fin'] ?? '');

            error_log("MSH Save Clase - Checking Avail Block #$index: Dia={$block_dia}, Start={$block_start_min}, End={$block_end_min}"); // LOG CADA BLOQUE

            // Comprobar si el día coincide Y el horario propuesto está DENTRO del bloque
            if ( $block_dia === $dia && $block_start_min !== false && $block_end_min !== false &&
                 $clase_start_min_prop >= $block_start_min && // Clase empieza DENTRO o IGUAL al inicio del bloque
                 $clase_end_min_prop <= $block_end_min        // Clase termina DENTRO o IGUAL al fin del bloque
               )
            {
                error_log("MSH Save Clase - MATCH Horario en Bloque #$index!"); // LOG MATCH HORARIO
                $is_within_availability = true;
                $checked_block = $b; // Guardar el bloque que coincide

                // Verificar Admisibilidad DENTRO de este bloque coincidente
                $sedes_admisibles = isset($b['sedes']) && is_array($b['sedes']) ? array_map('absint', $b['sedes']) : [];
                $programas_admisibles = isset($b['programas']) && is_array($b['programas']) ? array_map('absint', $b['programas']) : [];
                $rangos_admisibles = isset($b['rangos']) && is_array($b['rangos']) ? array_map('absint', $b['rangos']) : [];

                error_log("MSH Save Clase - Bloque #$index - Admisibles: Sedes=" . implode(',',$sedes_admisibles) . " / Progs=" . implode(',',$programas_admisibles) . " / Rangos=" . implode(',',$rangos_admisibles)); // LOG ADMISIBLES
                error_log("MSH Save Clase - Bloque #$index - Intentando asignar: Sede=$sede_id / Prog=$programa_id / Rango=$rango_id"); // LOG INTENTO

                if ( in_array( $sede_id, $sedes_admisibles ) &&
                     in_array( $programa_id, $programas_admisibles ) &&
                     in_array( $rango_id, $rangos_admisibles ) )
                {
                    error_log("MSH Save Clase - MATCH Admisibilidad en Bloque #$index!"); // LOG MATCH ADMISIBILIDAD
                    $is_admissible = true;
                    break; // Encontramos un bloque válido y admisible, no necesitamos seguir buscando
                } else {
                     error_log("MSH Save Clase - FAIL Admisibilidad en Bloque #$index."); // LOG FALLO ADMISIBILIDAD
                }
            } else {
                 error_log("MSH Save Clase - No Match Horario en Bloque #$index.");
            }
        } // Fin foreach disponibilidad
    } else {
         error_log("MSH Save Clase - ERROR: Hora propuesta inválida en minutos.");
    }


    if ( !$is_within_availability ) {
        $errors[] = __('Horario fuera de disponibilidad general.'); // Mensaje de error
        error_log("MSH Save Clase - ERROR: Validación Falló - Horario fuera de disponibilidad.");
    } elseif ( !$is_admissible ) {
        $errors[] = __('Combinación Sede/Programa/Rango no admisible para este horario.'); // Mensaje de error
        error_log("MSH Save Clase - ERROR: Validación Falló - Combinación no admisible en el bloque coincidente.");
    }
    if (!empty($errors)) {
        wp_send_json_error( array( 'message' => implode('<br>', $errors) ) );
    }
    error_log("MSH Save Clase - Validación Disponibilidad/Admisibilidad OK.");
    // ---> FIN DEBUG VALIDACIÓN DISPONIBILIDAD <---


    // c) Solapamiento con otras clases
    error_log("MSH Save Clase - Iniciando Validación Solapamiento...");
    // ... (lógica validación solapamiento como antes) ...
    $args_overlap = array(/* ... */); $otras_clases_query = new WP_Query($args_overlap);
    if ( $otras_clases_query->have_posts() ) {
         foreach ($otras_clases_query->posts as $otra_clase) { /* ... lógica chequeo ... */ }
    }
    if (!empty($errors)) {
         error_log("MSH Save Clase - ERROR: Validación Falló - Solapamiento detectado.");
         wp_send_json_error( array( 'message' => implode('<br>', $errors) ) );
    }
     error_log("MSH Save Clase - Validación Solapamiento OK.");


    // d) Validación/Advertencia de Proximidad (Traslado)
    $proximity_warning = '';
    // Re-usar la query anterior $otras_clases_query (clases del mismo maestro y día)
    if ( $otras_clases_query->have_posts() ) {
        $clases_dia = $otras_clases_query->posts;
        // Añadir la clase actual (si se está editando) para la comparación completa
        if ($clase_id > 0) {
            $current_post = get_post($clase_id);
            if ($current_post) $clases_dia[] = $current_post;
        }
        // Ordenar por hora de inicio para encontrar adyacentes
        usort($clases_dia, function($a, $b) {
             $time_a = strtotime(get_post_meta($a->ID, '_msh_clase_hora_inicio', true));
             $time_b = strtotime(get_post_meta($b->ID, '_msh_clase_hora_inicio', true));
             return $time_a - $time_b;
        });
        $clase_anterior = null;
        $clase_siguiente = null;
        $new_class_data_temp = ['ID' => $clase_id, 'start' => $start_time_new, 'end' => $end_time_new, 'sede' => $sede_id]; // Simular la nueva/editada
        // Encontrar posición de la clase nueva/editada y sus vecinas
        foreach ($clases_dia as $index => $clase_existente) {
             $start_existing = strtotime(get_post_meta($clase_existente->ID, '_msh_clase_hora_inicio', true));
             if ($start_existing > $new_class_data_temp['start']) {
                 $clase_siguiente = $clase_existente;
                 if ($index > 0) {
                     $clase_anterior = $clases_dia[$index - 1];
                 }
                 break;
             }
             // Si es la última y no hemos encontrado siguiente
             if ($index === count($clases_dia) - 1) {
                 $clase_anterior = $clase_existente;
             }
        }
         // Si la nueva clase es la primera
         if ($clase_siguiente === null && count($clases_dia) > 0 && $new_class_data_temp['start'] < strtotime(get_post_meta($clases_dia[0]->ID, '_msh_clase_hora_inicio', true))) {
             $clase_siguiente = $clases_dia[0];
         }

        // Verificar hueco ANTES de la nueva clase
        if ($clase_anterior) {
            $sede_anterior = absint(get_post_meta($clase_anterior->ID, '_msh_clase_sede_id', true));
            if ($sede_anterior !== $new_class_data_temp['sede']) {
                $end_time_anterior = strtotime(get_post_meta($clase_anterior->ID, '_msh_clase_hora_fin', true));
                $gap_before = $new_class_data_temp['start'] - $end_time_anterior; // en segundos
                $required_travel_time = msh_get_required_travel_time($end_time_anterior); // Obtener tiempo necesario
                if ($gap_before < ($required_travel_time * 60)) { // Convertir minutos a segundos
                    $proximity_warning .= sprintf(__(' ¡Atención! Tiempo de traslado ajustado antes de esta clase desde Sede %s (%d min requeridos, %d min disponibles).', 'music-schedule-manager'), get_the_title($sede_anterior), $required_travel_time, floor($gap_before / 60)) . '<br>';
                }
            }
        }
        // Verificar hueco DESPUÉS de la nueva clase
         if ($clase_siguiente) {
            $sede_siguiente = absint(get_post_meta($clase_siguiente->ID, '_msh_clase_sede_id', true));
            if ($sede_siguiente !== $new_class_data_temp['sede']) {
                $start_time_siguiente = strtotime(get_post_meta($clase_siguiente->ID, '_msh_clase_hora_inicio', true));
                $gap_after = $start_time_siguiente - $new_class_data_temp['end']; // en segundos
                $required_travel_time = msh_get_required_travel_time($new_class_data_temp['end']); // Tiempo basado en la hora de fin de la clase actual
                if ($gap_after < ($required_travel_time * 60)) {
                     $proximity_warning .= sprintf(__(' ¡Atención! Tiempo de traslado ajustado después de esta clase hacia Sede %s (%d min requeridos, %d min disponibles).', 'music-schedule-manager'), get_the_title($sede_siguiente), $required_travel_time, floor($gap_after / 60));
                }
            }
        }
    }
     error_log("MSH Save Clase - Advertencia Proximidad (si existe): " . $proximity_warning);


    // 4. Preparar Datos y Guardar Post
    // *** INICIALIZAR $post_data AQUÍ ***
    $post_data = array(
        'post_type'   => 'msh_clase',
        'post_status' => 'publish',
        // Añadir otros campos si son necesarios, como post_author si no usas meta para maestro_id
        // 'post_author' => $maestro_id,
    );

    // Generar título
    $maestro_name = get_the_title($maestro_id);
    $programa_name = get_the_title($programa_id);
    $rango_name = get_the_title($rango_id);
    $sede_name = get_the_title($sede_id);
    $dias_semana_map = msh_get_dias_semana(); // Usar helper
    $dia_name = $dias_semana_map[$dia] ?? ucfirst($dia);
    $post_title = sprintf('%s %s-%s | %s (%s) | %s | %s', $dia_name, $hora_inicio, $hora_fin, $programa_name, $rango_name, $sede_name, $maestro_name);

    // *** ASIGNAR Título a $post_data ***
    $post_data['post_title'] = $post_title;

    // Añadir ID si es una actualización
    if ( $clase_id > 0 ) {

        // *** VALIDACIÓN EXTRA ANTES DE ACTUALIZAR ***
        $post_to_update = get_post($clase_id);
        if (!$post_to_update || $post_to_update->post_type !== 'msh_clase') {
             error_log("MSH Save Clase - ERROR: Intentando actualizar Post ID $clase_id, pero no existe o no es msh_clase.");
             wp_send_json_error( array( 'message' => __('Error: La clase que intentas actualizar no existe o es inválida.', 'music-schedule-manager') ) );
        }
         // Verificar si pertenece al maestro (redundante si ya se hizo en load_form, pero seguro)
         $clase_maestro_id_meta = get_post_meta( $clase_id, '_msh_clase_maestro_id', true );
         if ( absint( $clase_maestro_id_meta ) !== $maestro_id ) {
              error_log("MSH Save Clase - ERROR: Intentando actualizar Post ID $clase_id, pero no pertenece al Maestro ID $maestro_id.");
              wp_send_json_error( array( 'message' => __('Error: No tienes permiso para actualizar esta clase.', 'music-schedule-manager') ) );
         }
        // *** FIN VALIDACIÓN EXTRA ***

        $post_data['ID'] = $clase_id;
    }
    error_log("MSH Save Clase - Preparando para guardar Post: " . print_r($post_data, true));
    $result = $clase_id > 0 ? wp_update_post( $post_data, true ) : wp_insert_post( $post_data, true );

    // 5. Guardar Metas y Enviar Respuesta
    if ( is_wp_error( $result ) ) {
        error_log("MSH Save Clase - ERROR: wp_insert/update_post falló: " . $result->get_error_message());
        wp_send_json_error( array( 'message' => $result->get_error_message() ) );
    } else {
        $new_clase_id = $result;
        error_log("MSH Save Clase - Post guardado/actualizado con ID: " . $new_clase_id);
        // Guardar Metas
        update_post_meta( $new_clase_id, '_msh_clase_maestro_id', $maestro_id );
        update_post_meta( $new_clase_id, '_msh_clase_dia', $dia );
        update_post_meta( $new_clase_id, '_msh_clase_hora_inicio', $hora_inicio );
        update_post_meta( $new_clase_id, '_msh_clase_hora_fin', $hora_fin );
        update_post_meta( $new_clase_id, '_msh_clase_sede_id', $sede_id );
        update_post_meta( $new_clase_id, '_msh_clase_programa_id', $programa_id );
        update_post_meta( $new_clase_id, '_msh_clase_rango_id', $rango_id );
        update_post_meta( $new_clase_id, '_msh_clase_capacidad', $capacidad );
        error_log("MSH Save Clase - Metadatos guardados para ID: " . $new_clase_id);

        wp_send_json_success( array('message' => __( 'Clase guardada.', 'music-schedule-manager' ), 'warning' => $proximity_warning, 'new_clase_id' => $new_clase_id, 'is_update' => ($clase_id > 0)) );
    }
     error_log("--- MSH Save Clase AJAX End ---");
}
add_action( 'wp_ajax_msh_save_clase', 'msh_ajax_save_clase_handler' );
// No añadir 'nopriv' para guardar

/**
 * Manejador AJAX para eliminar una Clase Programada.
 */
function msh_ajax_delete_clase_handler() {
     check_ajax_referer( 'msh_manage_clases_action', 'security' );
     $required_capability_delete = apply_filters('msh_capability_delete_clases', 'delete_posts');
     if ( ! current_user_can( $required_capability_delete ) ) {
         wp_send_json_error( array( 'message' => __( 'No tienes permiso para eliminar.', 'music-schedule-manager' ) ) );
     }
     $clase_id = isset( $_POST['clase_id'] ) ? absint( $_POST['clase_id'] ) : 0;
     if ( !$clase_id || get_post_type($clase_id) !== 'msh_clase' ) {
         wp_send_json_error( array( 'message' => __( 'ID de Clase inválido.', 'music-schedule-manager' ) ) );
     }
     $result = wp_delete_post( $clase_id, true ); // Forzar borrado
     if ($result) { wp_send_json_success( array( 'message' => __( 'Clase eliminada.', 'music-schedule-manager' ) ) ); }
     else { wp_send_json_error( array( 'message' => __( 'Error al eliminar la clase.', 'music-schedule-manager' ) ) ); }
}
add_action( 'wp_ajax_msh_delete_clase', 'msh_ajax_delete_clase_handler' );
// No añadir 'nopriv' para eliminar

?>