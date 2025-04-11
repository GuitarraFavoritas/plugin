<?php
// includes/meta-boxes/maestro-clases.php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Registra la Meta Box para mostrar y gestionar las clases programadas del maestro.
 */
function msh_add_maestro_clases_meta_box() {
    add_meta_box(
        'msh_maestro_clases_asignadas',            // ID único
        __( 'Clases Programadas', 'music-schedule-manager' ), // Título
        'msh_maestro_clases_metabox_render',       // Callback para el HTML
        'msh_maestro',                           // CPT donde aparece
        'normal',                                // Contexto
        'high'                                   // Prioridad
    );
}
add_action( 'add_meta_boxes_msh_maestro', 'msh_add_maestro_clases_meta_box' );
/**
 * Renderiza el contenido de la Meta Box de Clases Programadas.
 * Muestra una tabla con las clases existentes y un botón para añadir nuevas.
 *
 * @param WP_Post $post El objeto del post actual (Maestro).
 */
function msh_maestro_clases_metabox_render( $post ) {
    $maestro_id = $post->ID;
    // Argumentos para la consulta de clases de ESTE maestro
    $args = array(
        'post_type' => 'msh_clase',
        'posts_per_page' => -1, // Obtener todas
        'post_status' => 'publish', // Solo clases publicadas (activas)
        'meta_key' => '_msh_clase_dia', // Campo por el que ordenar (primero día)
        'orderby' => 'meta_value',
        'order' => 'ASC', // Orden alfabético de días (ajustar si se necesita orden L-D)
        // Filtrar por maestro (cambiar 'meta_key' si usas post_author en lugar de meta)
        'meta_query' => array(
             array(
                'key' => '_msh_clase_maestro_id',
                'value' => $maestro_id,
                'compare' => '=',
                'type' => 'NUMERIC'
            )
            // Añadir aquí un segundo 'orderby' si se quiere ordenar por hora_inicio después de día
            // WP_Query multi-orderby con meta es complejo, podría requerir ordenar con PHP después
        )
    );
    $clases_query = new WP_Query( $args );
    // Necesitaremos un nonce para las acciones (añadir/editar/borrar)
    wp_nonce_field( 'msh_manage_clases_action', 'msh_manage_clases_nonce' );
    ?>
    <div class="msh-clases-container">
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
                        <th><?php esc_html_e('Acciones', 'music-schedule-manager'); ?></th>
                    </tr>
                </thead>
                <tbody id="msh-clases-list">
                    <?php if ( $clases_query->have_posts() ) : ?>
                        <?php
                        // Ordenar por día (L-D) y luego hora inicio usando PHP si WP_Query no lo hace bien
                        $clases_posts = $clases_query->posts;
                        usort($clases_posts, 'msh_sort_clases_callback');
                        foreach ( $clases_posts as $clase_post ) :
                            $clase_id = $clase_post->ID;
                            // Obtener los meta datos de la clase
                            $dia = get_post_meta( $clase_id, '_msh_clase_dia', true );
                            $hora_inicio = get_post_meta( $clase_id, '_msh_clase_hora_inicio', true );
                            $hora_fin = get_post_meta( $clase_id, '_msh_clase_hora_fin', true );
                            $programa_id = get_post_meta( $clase_id, '_msh_clase_programa_id', true );
                            $rango_id = get_post_meta( $clase_id, '_msh_clase_rango_id', true );
                            $sede_id = get_post_meta( $clase_id, '_msh_clase_sede_id', true );
                            $capacidad = get_post_meta( $clase_id, '_msh_clase_capacidad', true );
                            // Obtener títulos para mostrar (manejar caso si el post relacionado fue borrado)
                            $programa_title = $programa_id ? get_the_title( $programa_id ) : 'N/A';
                            $rango_title = $rango_id ? get_the_title( $rango_id ) : 'N/A';
                            $sede_title = $sede_id ? get_the_title( $sede_id ) : 'N/A';
                            // Formatear día y hora
                            $dias_semana_disp = msh_get_dias_semana(); // Obtener días traducidos
                            $dia_display = isset($dias_semana_disp[$dia]) ? $dias_semana_disp[$dia] : ucfirst($dia);
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
                                    <button type="button" class="button button-small msh-edit-clase" data-clase-id="<?php echo esc_attr( $clase_id ); ?>">
                                        <?php esc_html_e('Editar', 'music-schedule-manager'); ?>
                                    </button>
                                    <button type="button" class="button button-small button-link-delete msh-delete-clase" data-clase-id="<?php echo esc_attr( $clase_id ); ?>">
                                         <?php esc_html_e('Eliminar', 'music-schedule-manager'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                         <?php wp_reset_postdata(); // Importante después de un loop personalizado ?>
                    <?php else : ?>
                        <tr id="msh-no-clases-row">
                            <td colspan="7"><?php esc_html_e('Este maestro no tiene clases programadas.', 'music-schedule-manager'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <p>
            <button type="button" id="msh-add-new-clase-btn" class="button button-primary" data-maestro-id="<?php echo esc_attr( $maestro_id ); ?>">
                <span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e('Añadir Nueva Clase', 'music-schedule-manager'); ?>
            </button>
        </p>
    </div>
    <?php // Aquí podríamos añadir un contenedor para el modal, inicialmente oculto ?>
    <div id="msh-clase-modal-container" style="display: none;">
         <div id="msh-clase-modal-content">
             <?php // El contenido del formulario se cargará aquí vía AJAX ?>
             <p><?php esc_html_e('Cargando formulario...', 'music-schedule-manager'); ?></p>
         </div>
    </div>
    <?php
}
/**
 * Función de comparación para ordenar las clases por día (L-D) y luego hora de inicio.
 * Usada con usort().
 */
function msh_sort_clases_callback($a, $b) {
    $dias_orden = array_flip(array_keys(msh_get_dias_semana())); // ['lunes' => 0, 'martes' => 1, ...]
    $dia_a = get_post_meta($a->ID, '_msh_clase_dia', true);
    $dia_b = get_post_meta($b->ID, '_msh_clase_dia', true);
    $orden_dia_a = isset($dias_orden[$dia_a]) ? $dias_orden[$dia_a] : 99;
    $orden_dia_b = isset($dias_orden[$dia_b]) ? $dias_orden[$dia_b] : 99;
    if ($orden_dia_a != $orden_dia_b) {
        return $orden_dia_a - $orden_dia_b; // Ordenar por día
    }
    // Si el día es el mismo, ordenar por hora de inicio
    $hora_a = get_post_meta($a->ID, '_msh_clase_hora_inicio', true);
    $hora_b = get_post_meta($b->ID, '_msh_clase_hora_inicio', true);
    // Convertir a timestamp para comparación numérica simple
    $time_a = strtotime($hora_a);
    $time_b = strtotime($hora_b);
    return $time_a - $time_b;
}
/**
 * Función auxiliar para obtener los días de la semana (para evitar repetición).
 */
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
// --- Aquí añadiremos las funciones AJAX (load_form, save, delete) ---
// require_once MSH_PLUGIN_DIR . 'includes/ajax-handlers/clase-handlers.php'; // O ponerlas directamente aquí

// =========================================================================
// =                         MANEJADORES AJAX                            =
// =========================================================================
// (Por simplicidad los ponemos aquí, pero podrían ir en un archivo aparte)
/**
 * Manejador AJAX para cargar el formulario de Añadir/Editar Clase.
 */
function msh_ajax_load_clase_form_handler() {
    // 1. Seguridad: Verificar Nonce y Permisos
    check_ajax_referer( 'msh_manage_clases_action', 'security' );
    if ( ! current_user_can( 'edit_posts' ) ) { // O una capacidad más específica
        wp_send_json_error( array( 'message' => __( 'Permiso denegado.', 'music-schedule-manager' ) ) );
        return;
    }
    // 2. Obtener datos de la petición
    $maestro_id = isset( $_POST['maestro_id'] ) ? absint( $_POST['maestro_id'] ) : 0;
    $clase_id   = isset( $_POST['clase_id'] ) ? absint( $_POST['clase_id'] ) : 0;
    if ( ! $maestro_id || get_post_type( $maestro_id ) !== 'msh_maestro' ) {
         wp_send_json_error( array( 'message' => __( 'ID de Maestro inválido.', 'music-schedule-manager' ) ) );
         return;
    }
    // 3. Obtener datos necesarios para el formulario
    $clase_data = array();
    if ( $clase_id > 0 ) {
        // Es edición, cargar datos existentes
        $clase_post = get_post( $clase_id );
        if ( ! $clase_post || $clase_post->post_type !== 'msh_clase' ) {
             wp_send_json_error( array( 'message' => __( 'ID de Clase inválido.', 'music-schedule-manager' ) ) );
             return;
        }
        // Asegurarse que la clase pertenezca al maestro (seguridad adicional)
        $clase_maestro_id = get_post_meta( $clase_id, '_msh_clase_maestro_id', true );
        if ( absint( $clase_maestro_id ) !== $maestro_id ) {
             wp_send_json_error( array( 'message' => __( 'Esta clase no pertenece al maestro especificado.', 'music-schedule-manager' ) ) );
            return;
        }
        $clase_data['dia'] = get_post_meta( $clase_id, '_msh_clase_dia', true );
        $clase_data['hora_inicio'] = get_post_meta( $clase_id, '_msh_clase_hora_inicio', true );
        $clase_data['hora_fin'] = get_post_meta( $clase_id, '_msh_clase_hora_fin', true );
        $clase_data['sede_id'] = get_post_meta( $clase_id, '_msh_clase_sede_id', true );
        $clase_data['programa_id'] = get_post_meta( $clase_id, '_msh_clase_programa_id', true );
        $clase_data['rango_id'] = get_post_meta( $clase_id, '_msh_clase_rango_id', true );
        $clase_data['capacidad'] = get_post_meta( $clase_id, '_msh_clase_capacidad', true );
    }
    // Obtener disponibilidad del maestro (para validación y filtrado dinámico)
    $maestro_disponibilidad = get_post_meta( $maestro_id, '_msh_maestro_disponibilidad', true );
    $maestro_disponibilidad = is_array( $maestro_disponibilidad ) ? $maestro_disponibilidad : array();
    // Obtener Sedes, Programas, Rangos para los <select>
    $sedes = get_posts( array( 'post_type' => 'msh_sede', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) );
    $programas = get_posts( array( 'post_type' => 'msh_programa', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) );
    $rangos = get_posts( array( 'post_type' => 'msh_rango_edad', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) );
    $dias_semana = msh_get_dias_semana();
    // 4. Generar el HTML del formulario (usando Output Buffering)
    ob_start();
    ?>
    <form id="msh-clase-form">
        <input type="hidden" name="maestro_id" value="<?php echo esc_attr( $maestro_id ); ?>">
        <input type="hidden" name="clase_id" value="<?php echo esc_attr( $clase_id ); ?>">
        <?php // Añadir nonce también al formulario del modal ?>
        <?php wp_nonce_field( 'msh_save_clase_action', 'msh_save_clase_nonce' ); ?>
        <h2><?php echo $clase_id > 0 ? esc_html__( 'Editar Clase', 'music-schedule-manager' ) : esc_html__( 'Añadir Nueva Clase', 'music-schedule-manager' ); ?></h2>
        <table class="form-table">
            <tbody>
                 <tr class="form-field">
                    <th><label for="msh_clase_dia"><?php esc_html_e( 'Día', 'music-schedule-manager' ); ?> <span class="description">(required)</span></label></th>
                    <td>
                        <select name="msh_clase_dia" id="msh_clase_dia" required>
                            <option value=""><?php esc_html_e('-- Seleccionar --', 'music-schedule-manager'); ?></option>
                            <?php foreach ($dias_semana as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($clase_data['dia'] ?? '', $key); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                 <tr class="form-field">
                    <th><label for="msh_clase_hora_inicio"><?php esc_html_e( 'Hora Inicio', 'music-schedule-manager' ); ?> <span class="description">(required)</span></label></th>
                    <td><input type="time" name="msh_clase_hora_inicio" id="msh_clase_hora_inicio" value="<?php echo esc_attr($clase_data['hora_inicio'] ?? ''); ?>" required></td>
                </tr>
                 <tr class="form-field">
                    <th><label for="msh_clase_hora_fin"><?php esc_html_e( 'Hora Fin', 'music-schedule-manager' ); ?> <span class="description">(required)</span></label></th>
                    <td><input type="time" name="msh_clase_hora_fin" id="msh_clase_hora_fin" value="<?php echo esc_attr($clase_data['hora_fin'] ?? ''); ?>" required></td>
                </tr>
                 <tr class="form-field">
                    <th><label for="msh_clase_sede_id"><?php esc_html_e( 'Sede', 'music-schedule-manager' ); ?> <span class="description">(required)</span></label></th>
                    <td>
                        <select name="msh_clase_sede_id" id="msh_clase_sede_id" required>
                             <option value=""><?php esc_html_e('-- Seleccionar --', 'music-schedule-manager'); ?></option>
                             <?php foreach ($sedes as $sede): ?>
                                <option value="<?php echo esc_attr($sede->ID); ?>" <?php selected($clase_data['sede_id'] ?? '', $sede->ID); ?> data-admisible="false"> <?php // data-admisible para JS ?>
                                    <?php echo esc_html($sede->post_title); ?>
                                </option>
                             <?php endforeach; ?>
                        </select>
                        <p class="description msh-availability-hint" style="display:none; color: red;"><?php esc_html_e('No disponible o no admisible para este horario/día.', 'music-schedule-manager'); ?></p>
                    </td>
                </tr>
                 <tr class="form-field">
                    <th><label for="msh_clase_programa_id"><?php esc_html_e( 'Programa', 'music-schedule-manager' ); ?> <span class="description">(required)</span></label></th>
                    <td>
                         <select name="msh_clase_programa_id" id="msh_clase_programa_id" required>
                             <option value=""><?php esc_html_e('-- Seleccionar --', 'music-schedule-manager'); ?></option>
                             <?php foreach ($programas as $programa): ?>
                                <option value="<?php echo esc_attr($programa->ID); ?>" <?php selected($clase_data['programa_id'] ?? '', $programa->ID); ?> data-admisible="false">
                                    <?php echo esc_html($programa->post_title); ?>
                                </option>
                             <?php endforeach; ?>
                        </select>
                         <p class="description msh-availability-hint" style="display:none; color: red;"><?php esc_html_e('No admisible para este horario/día.', 'music-schedule-manager'); ?></p>
                    </td>
                </tr>
                 <tr class="form-field">
                    <th><label for="msh_clase_rango_id"><?php esc_html_e( 'Rango Edad', 'music-schedule-manager' ); ?> <span class="description">(required)</span></label></th>
                     <td>
                         <select name="msh_clase_rango_id" id="msh_clase_rango_id" required>
                             <option value=""><?php esc_html_e('-- Seleccionar --', 'music-schedule-manager'); ?></option>
                             <?php foreach ($rangos as $rango): ?>
                                <option value="<?php echo esc_attr($rango->ID); ?>" <?php selected($clase_data['rango_id'] ?? '', $rango->ID); ?> data-admisible="false">
                                    <?php echo esc_html($rango->post_title); ?>
                                </option>
                             <?php endforeach; ?>
                        </select>
                        <p class="description msh-availability-hint" style="display:none; color: red;"><?php esc_html_e('No admisible para este horario/día.', 'music-schedule-manager'); ?></p>
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label for="msh_clase_capacidad"><?php esc_html_e( 'Capacidad Máx.', 'music-schedule-manager' ); ?> <span class="description">(required)</span></label></th>
                    <td><input type="number" name="msh_clase_capacidad" id="msh_clase_capacidad" value="<?php echo esc_attr($clase_data['capacidad'] ?? '1'); ?>" min="1" step="1" required style="width: 80px;"></td>
                </tr>
            </tbody>
        </table>
        <div id="msh-clase-validation-messages" style="color: red; margin-bottom: 10px;"></div>
        <div id="msh-clase-proximity-warning" style="color: orange; margin-bottom: 10px;"></div>

        <p class="submit">
            <button type="submit" class="button button-primary" id="msh-save-clase-btn">
                <?php echo $clase_id > 0 ? esc_html__( 'Actualizar Clase', 'music-schedule-manager' ) : esc_html__( 'Guardar Clase', 'music-schedule-manager' ); ?>
            </button>
            <button type="button" class="button button-secondary msh-cancel-clase-btn">
                <?php esc_html_e('Cancelar', 'music-schedule-manager'); ?>
            </button>
            <span class="spinner"></span>
        </p>
    </form>
    <?php
    $form_html = ob_get_clean();
    // 5. Enviar respuesta JSON
    wp_send_json_success( array(
        'html' => $form_html,
        // Pasamos la disponibilidad del maestro al JS para el filtrado dinámico
        'maestro_availability' => $maestro_disponibilidad
    ) );
}
add_action( 'wp_ajax_msh_load_clase_form', 'msh_ajax_load_clase_form_handler' );

/**
 * Manejador AJAX para guardar (crear/actualizar) una Clase Programada.
 */
function msh_ajax_save_clase_handler() {
    // 1. Seguridad: Verificar Nonce y Permisos
    check_ajax_referer( 'msh_save_clase_action', 'security' );
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permiso denegado.', 'music-schedule-manager' ) ) );
        return;
    }
    // 2. Obtener y Sanitizar Datos del $_POST
    $clase_id   = isset( $_POST['clase_id'] ) ? absint( $_POST['clase_id'] ) : 0;
    $maestro_id = isset( $_POST['maestro_id'] ) ? absint( $_POST['maestro_id'] ) : 0;
    $dia        = isset( $_POST['msh_clase_dia'] ) ? sanitize_key( $_POST['msh_clase_dia'] ) : '';
    $hora_inicio= isset( $_POST['msh_clase_hora_inicio'] ) ? sanitize_text_field( wp_strip_all_tags( $_POST['msh_clase_hora_inicio'] ) ) : '';
    $hora_fin   = isset( $_POST['msh_clase_hora_fin'] ) ? sanitize_text_field( wp_strip_all_tags( $_POST['msh_clase_hora_fin'] ) ) : '';
    $sede_id    = isset( $_POST['msh_clase_sede_id'] ) ? absint( $_POST['msh_clase_sede_id'] ) : 0;
    $programa_id= isset( $_POST['msh_clase_programa_id'] ) ? absint( $_POST['msh_clase_programa_id'] ) : 0;
    $rango_id   = isset( $_POST['msh_clase_rango_id'] ) ? absint( $_POST['msh_clase_rango_id'] ) : 0;
    $capacidad  = isset( $_POST['msh_clase_capacidad'] ) ? absint( $_POST['msh_clase_capacidad'] ) : 1;
    if ($capacidad < 1) $capacidad = 1; // Asegurar mínimo 1
    $dias_permitidos = array_keys( msh_get_dias_semana() );
    // 3. Validaciones Cruciales
    $errors = array();
    // a) Campos requeridos básicos
    if ( empty($maestro_id) || get_post_type($maestro_id) !== 'msh_maestro' ) $errors[] = __('ID de Maestro inválido.', 'music-schedule-manager');
    if ( empty($dia) || !in_array($dia, $dias_permitidos) ) $errors[] = __('Día inválido o faltante.', 'music-schedule-manager');
    if ( empty($hora_inicio) || !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $hora_inicio) ) $errors[] = __('Hora de inicio inválida o faltante (HH:MM).', 'music-schedule-manager');
    if ( empty($hora_fin) || !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $hora_fin) ) $errors[] = __('Hora de fin inválida o faltante (HH:MM).', 'music-schedule-manager');
    if ( strtotime($hora_fin) <= strtotime($hora_inicio) ) $errors[] = __('La hora de fin debe ser posterior a la hora de inicio.', 'music-schedule-manager');
    if ( empty($sede_id) || get_post_type($sede_id) !== 'msh_sede' || get_post_status($sede_id) !== 'publish') $errors[] = __('Sede inválida o faltante.', 'music-schedule-manager');
    if ( empty($programa_id) || get_post_type($programa_id) !== 'msh_programa' || get_post_status($programa_id) !== 'publish') $errors[] = __('Programa inválido o faltante.', 'music-schedule-manager');
    if ( empty($rango_id) || get_post_type($rango_id) !== 'msh_rango_edad' || get_post_status($rango_id) !== 'publish') $errors[] = __('Rango de edad inválido o faltante.', 'music-schedule-manager');
    if ( !empty($errors) ) {
        wp_send_json_error( array( 'message' => implode( '<br>', $errors ) ) );
        return;
    }
    // b) Validación contra Disponibilidad del Maestro
    $maestro_disponibilidad = get_post_meta( $maestro_id, '_msh_maestro_disponibilidad', true );
    $maestro_disponibilidad = is_array( $maestro_disponibilidad ) ? $maestro_disponibilidad : array();
    $is_within_availability = false;
    $is_admissible = false;
    foreach ( $maestro_disponibilidad as $bloque_disp ) {
        if ( $bloque_disp['dia'] === $dia &&
             strtotime( $hora_inicio ) >= strtotime( $bloque_disp['hora_inicio'] ) &&
             strtotime( $hora_fin ) <= strtotime( $bloque_disp['hora_fin'] ) )
        {
            $is_within_availability = true;
            // Verificar si la Sede, Programa y Rango están permitidos en ESTE bloque de disponibilidad
            if ( in_array( $sede_id, $bloque_disp['sedes'] ?? [] ) &&
                 in_array( $programa_id, $bloque_disp['programas'] ?? [] ) &&
                 in_array( $rango_id, $bloque_disp['rangos'] ?? [] ) )
            {
                $is_admissible = true;
                break; // Encontramos un bloque válido y admisible
            }
        }
    }
    if ( !$is_within_availability ) {
        $errors[] = __('El horario seleccionado ('.$hora_inicio.'-'.$hora_fin.') no está dentro de ningún bloque de disponibilidad general del maestro para el día '.$dia.'.', 'music-schedule-manager');
    } elseif ( !$is_admissible ) {
        $errors[] = __('La combinación Sede/Programa/Rango seleccionada no es admisible para el horario y día especificados según la disponibilidad general del maestro.', 'music-schedule-manager');
    }
     if ( !empty($errors) ) {
        wp_send_json_error( array( 'message' => implode( '<br>', $errors ) ) );
        return;
    }
    // c) Validación de solapamiento con OTRAS clases del MISMO maestro
    $args_overlap = array(
        'post_type' => 'msh_clase',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'post__not_in' => array( $clase_id ), // Excluir la clase actual si estamos editando
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => '_msh_clase_maestro_id',
                'value' => $maestro_id,
                'compare' => '=',
            ),
            array(
                'key' => '_msh_clase_dia',
                'value' => $dia,
                'compare' => '=',
            ),
             // La lógica de solapamiento de tiempo es compleja con meta_query
             // Es más fácil obtener todas las del día y maestro y verificar con PHP
        )
    );
    $otras_clases_query = new WP_Query($args_overlap);
    $start_time_new = strtotime($hora_inicio);
    $end_time_new = strtotime($hora_fin);
    if ( $otras_clases_query->have_posts() ) {
        foreach ($otras_clases_query->posts as $otra_clase) {
            $start_time_existing = strtotime(get_post_meta($otra_clase->ID, '_msh_clase_hora_inicio', true));
            $end_time_existing = strtotime(get_post_meta($otra_clase->ID, '_msh_clase_hora_fin', true));
            // Comprobar solapamiento: (StartA < EndB) and (EndA > StartB)
            if ( $start_time_new < $end_time_existing && $end_time_new > $start_time_existing ) {
                 $errors[] = sprintf(
                    __('El horario %s-%s se solapa con otra clase existente (%s-%s) para este maestro el día %s.', 'music-schedule-manager'),
                    $hora_inicio, $hora_fin,
                    date('H:i', $start_time_existing), date('H:i', $end_time_existing),
                    $dia
                );
                break; // Solo necesitamos encontrar un solapamiento
            }
        }
    }
    if ( !empty($errors) ) {
        wp_send_json_error( array( 'message' => implode( '<br>', $errors ) ) );
        return;
    }
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

    // 4. Preparar Datos para Guardar
    $maestro_name = get_the_title($maestro_id);
    $programa_name = get_the_title($programa_id);
    $rango_name = get_the_title($rango_id);
    $sede_name = get_the_title($sede_id);
    $dias_semana_disp = msh_get_dias_semana();
    $dia_name = $dias_semana_disp[$dia] ?? ucfirst($dia);
    // Título autogenerado
    $post_title = sprintf('%s %s-%s - %s (%s) - %s - %s',
        $dia_name, $hora_inicio, $hora_fin,
        $programa_name, $rango_name, $sede_name, $maestro_name
    );
    $post_data = array(
        'post_type'   => 'msh_clase',
        'post_status' => 'publish',
        'post_title'  => $post_title,
        // 'post_author' => $maestro_id, // Alternativa a guardar el meta _msh_clase_maestro_id
    );
    if ( $clase_id > 0 ) {
        $post_data['ID'] = $clase_id; // Indicar que es una actualización
        $result = wp_update_post( $post_data, true ); // true para devolver WP_Error en caso de fallo
    } else {
        $result = wp_insert_post( $post_data, true );
    }
    // 5. Guardar Metadatos y Enviar Respuesta
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( array( 'message' => __( 'Error al guardar la clase: ', 'music-schedule-manager' ) . $result->get_error_message() ) );
    } else {
        $new_clase_id = $result; // ID del post insertado o actualizado
        // Guardar/Actualizar metadatos
        update_post_meta( $new_clase_id, '_msh_clase_maestro_id', $maestro_id );
        update_post_meta( $new_clase_id, '_msh_clase_dia', $dia );
        update_post_meta( $new_clase_id, '_msh_clase_hora_inicio', $hora_inicio );
        update_post_meta( $new_clase_id, '_msh_clase_hora_fin', $hora_fin );
        update_post_meta( $new_clase_id, '_msh_clase_sede_id', $sede_id );
        update_post_meta( $new_clase_id, '_msh_clase_programa_id', $programa_id );
        update_post_meta( $new_clase_id, '_msh_clase_rango_id', $rango_id );
        update_post_meta( $new_clase_id, '_msh_clase_capacidad', $capacidad );
        // Obtener HTML de la fila actualizada/nueva para devolver al JS y refrescar la tabla
        $dia_display = isset($dias_semana_disp[$dia]) ? $dias_semana_disp[$dia] : ucfirst($dia);
        $horario_display = esc_html( $hora_inicio . ' - ' . $hora_fin );
        $programa_title = $programa_id ? get_the_title( $programa_id ) : 'N/A';
        $rango_title = $rango_id ? get_the_title( $rango_id ) : 'N/A';
        $sede_title = $sede_id ? get_the_title( $sede_id ) : 'N/A';
        ob_start();
        ?>
         <tr id="msh-clase-row-<?php echo esc_attr( $new_clase_id ); ?>">
            <td><?php echo esc_html( $dia_display ); ?></td>
            <td><?php echo $horario_display; ?></td>
            <td><?php echo esc_html( $programa_title ); ?></td>
            <td><?php echo esc_html( $rango_title ); ?></td>
            <td><?php echo esc_html( $sede_title ); ?></td>
            <td><?php echo esc_html( $capacidad ); ?></td>
            <td>
                <button type="button" class="button button-small msh-edit-clase" data-clase-id="<?php echo esc_attr( $new_clase_id ); ?>">
                    <?php esc_html_e('Editar', 'music-schedule-manager'); ?>
                </button>
                <button type="button" class="button button-small button-link-delete msh-delete-clase" data-clase-id="<?php echo esc_attr( $new_clase_id ); ?>">
                    <?php esc_html_e('Eliminar', 'music-schedule-manager'); ?>
                </button>
            </td>
        </tr>
        <?php
        $new_row_html = ob_get_clean();

        wp_send_json_success( array(
            'message' => __( 'Clase guardada correctamente.', 'music-schedule-manager' ),
            'warning' => $proximity_warning, // Enviar advertencia de proximidad si existe
            'new_clase_id' => $new_clase_id,
            'new_row_html' => $new_row_html,
            'is_update' => ($clase_id > 0) // Indicar si fue una actualización o inserción
        ) );
    }
}
add_action( 'wp_ajax_msh_save_clase', 'msh_ajax_save_clase_handler' );

/**
 * Manejador AJAX para eliminar una Clase Programada.
 */
function msh_ajax_delete_clase_handler() {
    // 1. Seguridad: Verificar Nonce y Permisos
    // Usar el nonce general 'msh_manage_clases_action' que está fuera de la tabla
     check_ajax_referer( 'msh_manage_clases_action', 'security' );
     if ( ! current_user_can( 'delete_posts' ) ) { // O una capacidad más específica
        wp_send_json_error( array( 'message' => __( 'Permiso denegado para eliminar.', 'music-schedule-manager' ) ) );
        return;
    }
    // 2. Obtener ID de la clase
    $clase_id = isset( $_POST['clase_id'] ) ? absint( $_POST['clase_id'] ) : 0;
    if ( !$clase_id || get_post_type($clase_id) !== 'msh_clase' ) {
        wp_send_json_error( array( 'message' => __( 'ID de Clase inválido.', 'music-schedule-manager' ) ) );
        return;
    }
    // 3. Eliminar el post
    $result = wp_delete_post( $clase_id, true ); // true = forzar borrado (no a la papelera)
    // 4. Enviar respuesta
    if ($result === false || $result === null) {
         wp_send_json_error( array( 'message' => __( 'Error al eliminar la clase.', 'music-schedule-manager' ) ) );
    } else {
         wp_send_json_success( array( 'message' => __( 'Clase eliminada correctamente.', 'music-schedule-manager' ) ) );
    }
}
add_action( 'wp_ajax_msh_delete_clase', 'msh_ajax_delete_clase_handler' );

/**
 * Calcula el tiempo de traslado requerido en minutos basado en la hora del día.
 *
 * @param int|string $timestamp_or_time Hora (timestamp o HH:MM) para determinar el rango.
 * @return int Tiempo de traslado en minutos.
 */
function msh_get_required_travel_time( $timestamp_or_time ): int {
    $hour = null;
    if ( is_numeric($timestamp_or_time) ) {
        $hour = (int) date('G', $timestamp_or_time); // Hora en formato 24h (0-23)
    } elseif (is_string($timestamp_or_time) && preg_match('/^([0-9]{1,2}):[0-9]{2}$/', $timestamp_or_time, $matches)) {
         $hour = (int) $matches[1];
    }
    if ($hour === null) return 60; // Valor por defecto si la hora es inválida
    // A partir de las 4:00 PM (hora 16) inclusive
    if ( $hour >= 16 ) {
        return 120; // 2 horas
    }
    // Antes de las 4:00 PM
    else {
        return 60; // 1 hora
    }
}
?>