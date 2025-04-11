<?php
// includes/cpt-registrations.php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Salir si se accede directamente.
}

/**
 * Función para registrar todos los CPTs.
 *
 * Se engancha a la acción 'init'.
 */
function msh_registrar_todos_cpts() {
	//CPT para Maestros 
	$labels_maestro = array(
		'name' => 'Maestros',
		'add_new_item' => 'Agregar Maestro',
		'edit_item' => 'Editar Maestro',
		'all_items' => 'Todos los Maestros',
		'singular_name' => 'Maestro'
	);

	$args_maestro = array(
		'labels'             => $labels_maestro,
		'public'             => true, // Hace el CPT visible en el frontend y admin
		'publicly_queryable' => true, // Permite consultas desde el frontend
		'show_ui'            => true, // Muestra la interfaz de usuario en el admin
		'show_in_menu'       => true, // Muestra en el menú de administración
		'query_var'          => true, // Permite usar '?maestro=nombre-maestro' en URLs
		'rewrite'            => array( 'slug' => 'maestros' ), // URL amigable (ej. tusitio.com/maestros/nombre-maestro)
		'capability_type'    => 'post', // Tipo de permisos (como las Entradas normales)
		'has_archive'        => 'maestros', // Activa una página de archivo en tusitio.com/maestros/
		'hierarchical'       => false, // No es jerárquico (como las Páginas)
		'menu_position'      => 5, // Posición en el menú del admin (5 es debajo de Entradas)
		'menu_icon'          => 'dashicons-businessman', // Ícono del menú (https://developer.wordpress.org/resource/dashicons/)
		'supports'           => array( 'title', 'thumbnail' ), // Campos que soporta: Título (Nombre), Editor (Biografía, etc.), Imagen Destacada (Foto)
		'show_in_rest'       => true, // Habilita el soporte para el editor de bloques (Gutenberg) y la API REST
	);

	register_post_type( 'msh_maestro', $args_maestro ); // Usamos 'msh_maestro' como slug interno con prefijo

	//CPT para Programas 
	$labels_programa = array(
		'name' => 'Programas',
		'add_new_item' => 'Agregar Programa',
		'edit_item' => 'Editar Programa',
		'all_items' => 'Todos los Programas',
		'singular_name' => 'Programa'
	);

	$args_programa = array(
		'labels'             => $labels_programa,
		'public'             => true, // Hace el CPT visible en el frontend y admin
		'publicly_queryable' => true, // Permite consultas desde el frontend
		'show_ui'            => true, // Muestra la interfaz de usuario en el admin
		'show_in_menu'       => true, // Muestra en el menú de administración
		'query_var'          => true, // Permite usar '?programa=nombre-programa' en URLs
		'rewrite'            => array( 'slug' => 'programas' ), // URL amigable (ej. tusitio.com/programas/nombre-programa)
		'capability_type'    => 'post', // Tipo de permisos (como las Entradas normales)
		'has_archive'        => 'programas', // Activa una página de archivo en tusitio.com/programas/
		'hierarchical'       => false, // No es jerárquico (como las Páginas)
		'menu_position'      => 5, // Posición en el menú del admin (5 es debajo de Entradas)
		'menu_icon'          => 'dashicons-welcome-learn-more', // Ícono del menú (https://developer.wordpress.org/resource/dashicons/)
		'supports'           => array( 'title', 'thumbnail' ), // Campos que soporta: Título (Nombre), Editor (Biografía, etc.), Imagen Destacada (Foto)
		'show_in_rest'       => true, // Habilita el soporte para el editor de bloques (Gutenberg) y la API REST
	);

	register_post_type( 'msh_programa', $args_programa ); // Usamos 'msh_programa' como slug interno con prefijo

	//CPT para Sedes 
	$labels_sede = array(
		'name' => 'Sedes',
		'add_new_item' => 'Agregar Sede',
		'edit_item' => 'Editar Sede',
		'all_items' => 'Todos los Sedes',
		'singular_name' => 'Sede'
	);

	$args_sede = array(
		'labels'             => $labels_sede,
		'public'             => true, // Hace el CPT visible en el frontend y admin
		'publicly_queryable' => true, // Permite consultas desde el frontend
		'show_ui'            => true, // Muestra la interfaz de usuario en el admin
		'show_in_menu'       => true, // Muestra en el menú de administración
		'query_var'          => true, // Permite usar '?sede=nombre-sede' en URLs
		'rewrite'            => array( 'slug' => 'sedes' ), // URL amigable (ej. tusitio.com/sedes/nombre-sede)
		'capability_type'    => 'post', // Tipo de permisos (como las Entradas normales)
		'has_archive'        => 'sedes', // Activa una página de archivo en tusitio.com/sedes/
		'hierarchical'       => false, // No es jerárquico (como las Páginas)
		'menu_position'      => 5, // Posición en el menú del admin (5 es debajo de Entradas)
		'menu_icon'          => 'dashicons-admin-multisite', // Ícono del menú (https://developer.wordpress.org/resource/dashicons/)
		'supports'           => array( 'title', 'thumbnail' ), // Campos que soporta: Título (Nombre), Editor (Biografía, etc.), Imagen Destacada (Foto)
		'show_in_rest'       => true, // Habilita el soporte para el editor de bloques (Gutenberg) y la API REST
	);

	register_post_type( 'msh_sede', $args_sede ); // Usamos 'msh_sede' como slug interno con prefijo

	//CPT para Rangos de Edades 
	$labels_rdedades = array(
		'name' => 'Rangos de Edades',
		'add_new_item' => 'Agregar Rango de Edad',
		'edit_item' => 'Editar Rango de Edad',
		'all_items' => 'Todos los Rangos de Edades',
		'singular_name' => 'Rango de Edad'
	);

	$args_rdedades = array(
		'labels'             => $labels_rdedades,
		'public'             => true, // Hace el CPT visible en el frontend y admin
		'publicly_queryable' => true, // Permite consultas desde el frontend
		'show_ui'            => true, // Muestra la interfaz de usuario en el admin
		'show_in_menu'       => true, // Muestra en el menú de administración
		'query_var'          => true, // Permite usar '?rango_de_edad=nombre-rango_de_edad' en URLs
		'rewrite'            => array( 'slug' => 'rangos_de_edades' ), // URL amigable (ej. tusitio.com/rangos_de_edades/nombre-rango_de_edad)
		'capability_type'    => 'post', // Tipo de permisos (como las Entradas normales)
		'has_archive'        => 'rangos_de_edades', // Activa una página de archivo en tusitio.com/rangos_de_edades/
		'hierarchical'       => false, // No es jerárquico (como las Páginas)
		'menu_position'      => 5, // Posición en el menú del admin (5 es debajo de Entradas)
		'menu_icon'          => 'dashicons-groups', // Ícono del menú (https://developer.wordpress.org/resource/dashicons/)
		'supports'           => array( 'title', 'thumbnail' ), // Campos que soporta: Título (Nombre), Editor (Biografía, etc.), Imagen Destacada (Foto)
		'show_in_rest'       => true, // Habilita el soporte para el editor de bloques (Gutenberg) y la API REST
	);

	register_post_type( 'msh_rango_edad', $args_rdedades ); // Usamos 'msh_rango_de_edad' como slug interno con prefijo

	//CPT para Horarios Programadas 
	$labels_rdedades = array(
		'name' => 'Asignados',
		'add_new_item' => 'Asignar',
		'edit_item' => 'Editar Asignado',
		'all_items' => 'Todos los Asignados',
		'singular_name' => 'Asignado'
	);

	$args_rdedades = array(
		'labels'             => $labels_rdedades,
		'public'             => false, // Hace el CPT visible en el frontend y admin
		'publicly_queryable' => true, // Permite consultas desde el frontend
		'show_ui'            => true, // Muestra la interfaz de usuario en el admin
		'show_in_menu'       => false, // Muestra en el menú de administración
		'query_var'          => true, // Permite usar '?clase_programada=nombre-clase_programada' en URLs
		'rewrite'            => array( 'slug' => 'horario_asignado' ), // URL amigable (ej. tusitio.com/clases_programadas/nombre-clase_programada)
		'capability_type'    => 'post', // Tipo de permisos (como las Entradas normales)
		'has_archive'        => 'horario_asignado', // Activa una página de archivo en tusitio.com/clases_programadas/
		'hierarchical'       => false, // No es jerárquico (como las Páginas)
		'menu_position'      => 5, // Posición en el menú del admin (5 es debajo de Entradas)
		'menu_icon'          => 'dashicons-clock', // Ícono del menú (https://developer.wordpress.org/resource/dashicons/)
		'supports'           => array( 'title', 'thumbnail' ), // Campos que soporta: Título (Nombre), Editor (Biografía, etc.), Imagen Destacada (Foto)
		'show_in_rest'       => true, // Habilita el soporte para el editor de bloques (Gutenberg) y la API REST
	);

	register_post_type( 'msh_clase', $args_rdedades ); // Usamos 'msh_clase' como slug interno con prefijo

}
add_action( 'init', 'msh_registrar_todos_cpts' );

?>