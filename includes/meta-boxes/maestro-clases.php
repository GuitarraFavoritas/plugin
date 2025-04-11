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


/**
 * Manejador AJAX para guardar (crear/actualizar) una Clase Programada.
 */
function msh_ajax_save_clase_handler() {
    // 1. Seguridad: Nonce y Permisos
    check_ajax_referer( 'msh_save_clase_action', 'security' );
    $required_capability_save = apply_filters('msh_capability_save_clases', 'publish_posts'); // Capacidad para guardar
    if ( ! current_user_can( $required_capability_save ) ) {
        wp_send_json_error( array( 'message' => __( 'No tienes permiso para guardar clases.', 'music-schedule-manager' ) ) );
    }

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

    // 3. Validaciones Cruciales
    $errors = array();
    $dias_permitidos = array_keys( msh_get_dias_semana() );
    // a) Campos requeridos básicos
    if ( empty($maestro_id) || get_post_type($maestro_id) !== 'msh_maestro' ) $errors[] = __('ID Maestro inválido.');
    if ( empty($dia) || !in_array($dia, $dias_permitidos) ) $errors[] = __('Día inválido.');
    if ( empty($hora_inicio) || !preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $hora_inicio) ) $errors[] = __('Hora inicio inválida.');
    if ( empty($hora_fin) || !preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $hora_fin) ) $errors[] = __('Hora fin inválida.');
    if ( !$errors && strtotime($hora_fin) <= strtotime($hora_inicio) ) $errors[] = __('Hora fin debe ser posterior a inicio.');
    if ( empty($sede_id) || get_post_type($sede_id) !== 'msh_sede' ) $errors[] = __('Sede inválida.');
    if ( empty($programa_id) || get_post_type($programa_id) !== 'msh_programa' ) $errors[] = __('Programa inválido.');
    if ( empty($rango_id) || get_post_type($rango_id) !== 'msh_rango_edad' ) $errors[] = __('Rango edad inválido.');
    if (!empty($errors)) wp_send_json_error( array( 'message' => implode('<br>', $errors) ) );

    // b) Disponibilidad y Admisibilidad
    $maestro_disponibilidad = get_post_meta( $maestro_id, '_msh_maestro_disponibilidad', true );
    $maestro_disponibilidad = is_array($maestro_disponibilidad) ? $maestro_disponibilidad : [];
    $is_within_availability = false; $is_admissible = false;
    foreach ( $maestro_disponibilidad as $b ) { /* ... lógica como antes ... */ }
    if (!$is_within_availability) $errors[] = __('Horario fuera de disponibilidad general.');
    elseif (!$is_admissible) $errors[] = __('Combinación Sede/Prog./Rango no admisible.');
    if (!empty($errors)) wp_send_json_error( array( 'message' => implode('<br>', $errors) ) );

    // c) Solapamiento con otras clases
    $args_overlap = array( /* ... como antes ... */ ); $otras_clases_query = new WP_Query($args_overlap);
    $start_time_new = strtotime($hora_inicio); $end_time_new = strtotime($hora_fin);
    if ( $otras_clases_query->have_posts() ) { /* ... lógica chequeo solapamiento ... */ }
    if (!empty($errors)) wp_send_json_error( array( 'message' => implode('<br>', $errors) ) );

    // d) Advertencia Proximidad
    $proximity_warning = '';
    // ... (lógica advertencia proximidad como antes, usando msh_get_required_travel_time) ...

    // 4. Preparar Datos y Guardar Post
    $maestro_name = get_the_title($maestro_id); $programa_name = get_the_title($programa_id); $rango_name = get_the_title($rango_id); $sede_name = get_the_title($sede_id); $dia_name = msh_get_dias_semana()[$dia] ?? ucfirst($dia);
    $post_title = sprintf('%s %s-%s | %s (%s) | %s | %s', $dia_name, $hora_inicio, $hora_fin, $programa_name, $rango_name, $sede_name, $maestro_name);
    $post_data = array('post_type' => 'msh_clase', 'post_status' => 'publish', 'post_title' => $post_title);
    if ( $clase_id > 0 ) $post_data['ID'] = $clase_id;
    $result = $clase_id > 0 ? wp_update_post( $post_data, true ) : wp_insert_post( $post_data, true );

    // 5. Guardar Metas y Enviar Respuesta
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( array( 'message' => $result->get_error_message() ) );
    } else {
        $new_clase_id = $result;
        update_post_meta( $new_clase_id, '_msh_clase_maestro_id', $maestro_id );
        update_post_meta( $new_clase_id, '_msh_clase_dia', $dia );
        update_post_meta( $new_clase_id, '_msh_clase_hora_inicio', $hora_inicio );
        update_post_meta( $new_clase_id, '_msh_clase_hora_fin', $hora_fin );
        update_post_meta( $new_clase_id, '_msh_clase_sede_id', $sede_id );
        update_post_meta( $new_clase_id, '_msh_clase_programa_id', $programa_id );
        update_post_meta( $new_clase_id, '_msh_clase_rango_id', $rango_id );
        update_post_meta( $new_clase_id, '_msh_clase_capacidad', $capacidad );
        wp_send_json_success( array('message' => __( 'Clase guardada.', 'music-schedule-manager' ), 'warning' => $proximity_warning, 'new_clase_id' => $new_clase_id, 'is_update' => ($clase_id > 0)) );
    }
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