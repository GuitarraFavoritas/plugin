// assets/js/frontend-script.js
jQuery(document).ready(function($) {

    // Asegurarse que los datos existen
    if (typeof msh_frontend_data === 'undefined' || !msh_frontend_data.ajax_url || !msh_frontend_data.nonce) {
        console.error('MSH Frontend Error: Faltan datos de configuración (ajax_url, nonce).');
        $('#msh-schedule-results-feedback').text('Error de configuración. Contacta al administrador.').show();
        return;
    }

    var filterForm = $('#msh-schedule-filters-form');
    var resultsContainer = $('#msh-schedule-results-table-container');
    var feedbackDiv = $('#msh-schedule-results-feedback');
    var initialMessage = $('#msh-initial-message'); // Mensaje inicial

     // --- Helper: Obtener nombre del día ---
     function getDayName(dayKey) {
         return (msh_frontend_data.days_of_week && msh_frontend_data.days_of_week[dayKey]) ? msh_frontend_data.days_of_week[dayKey] : dayKey;
     }
     // --- Helper: Obtener nombre del maestro ---
      function getMaestroName(id) {
          return (msh_frontend_data.maestro_names && msh_frontend_data.maestro_names[id]) ? msh_frontend_data.maestro_names[id] : `Maestro ID:${id}`;
      }
     // --- Helper: Obtener nombres (Sede, Programa, Rango) desde IDs ---
      function getNamesFromIds(ids, map) {
          if (!Array.isArray(ids) || !map) return [];
          return ids.map(id => map[id] || `ID:${id}?`).filter(name => name);
      }


    // --- Manejar Envío del Formulario de Filtros ---
    filterForm.on('submit', function(e) {
        e.preventDefault(); // Prevenir recarga de página

        var formData = $(this).serialize(); // Obtener datos del formulario serializados
        var submitButton = $(this).find('.msh-filter-submit-btn');

        // Añadir acción y nonce a los datos
        var dataToSend = formData +
                         '&action=msh_filter_schedule' +
                         '&nonce=' + msh_frontend_data.nonce;

        $.ajax({
            url: msh_frontend_data.ajax_url,
            type: 'POST',
            data: dataToSend,
            beforeSend: function() {
                // Mostrar estado de carga
                submitButton.prop('disabled', true);
                initialMessage.hide(); // Ocultar mensaje inicial
                feedbackDiv.text(msh_frontend_data.loading_message || 'Buscando...').removeClass('msh-error').addClass('msh-loading').show();
                resultsContainer.empty().append('<div class="msh-spinner"></div>'); // Limpiar tabla y mostrar spinner CSS
            },
            success: function(response) {
                feedbackDiv.hide().removeClass('msh-loading'); // Ocultar mensaje de carga

                if (response.success) {
                    // Renderizar la tabla con los resultados
                    renderResultsTable(response.data.schedule_data);
                    if (response.data.schedule_data.length === 0) {
                         feedbackDiv.text(msh_frontend_data.no_results_message || 'No se encontraron resultados.').show();
                    }
                } else {
                    // Mostrar mensaje de error del servidor
                    var errorMsg = response.data.message || msh_frontend_data.error_message || 'Ocurrió un error.';
                    feedbackDiv.text(errorMsg).addClass('msh-error').show();
                    resultsContainer.empty(); // Limpiar contenedor de tabla en caso de error
                }
            },
            error: function() {
                // Mostrar mensaje de error de conexión
                 feedbackDiv.text(msh_frontend_data.error_message || 'Ocurrió un error de conexión.').addClass('msh-error').show();
                 resultsContainer.empty();
            },
            complete: function() {
                // Reactivar botón de envío
                submitButton.prop('disabled', false);
                resultsContainer.find('.msh-spinner').remove(); // Quitar spinner
            }
        });
    });

    // --- Manejar Reset del Formulario ---
    filterForm.on('reset', function() {
        // Limpiar resultados y mensajes al resetear
        resultsContainer.empty();
        feedbackDiv.hide().text('');
        initialMessage.show(); // Mostrar mensaje inicial de nuevo
    });


    // --- Función para Renderizar la Tabla de Resultados ---
    function renderResultsTable(scheduleData) {
        resultsContainer.empty(); // Limpiar contenedor

        if (!scheduleData || scheduleData.length === 0) {
            // El mensaje de "no resultados" se muestra en feedbackDiv en el success del AJAX
            return;
        }

        // Crear estructura de la tabla
        var tableHtml = `
            <table class="msh-results-table">
                <thead>
                    <tr>
                        <th>${'Horario'}</th>
                        <th>${'Maestro'}</th>
                        <th>${'Programa'}</th>
                        <th>${'Sede'}</th>
                        <th>${'Rango de Edades'}</th>
                        <th>${'Disponibilidad'}</th>
                        <th>${'Acciones'}</th>
                    </tr>
                </thead>
                <tbody>
        `;

        // Iterar sobre los datos y construir filas
        $.each(scheduleData, function(index, item) {
            let horario = `${getDayName(item.dia)} ${item.hora_inicio}-${item.hora_fin}`;
            let maestro = getMaestroName(item.maestro_id);
            let programaDisplay, sedeDisplay, rangoDisplay, disponibilidad, acciones;

            if (item.type === 'asignado') {
                programaDisplay = msh_frontend_data.programa_names[item.programa_id] || `ID:${item.programa_id}`;
                sedeDisplay = msh_frontend_data.sede_names[item.sede_id] || `ID:${item.sede_id}`;
                rangoDisplay = msh_frontend_data.rango_names[item.rango_id] || `ID:${item.rango_id}`;
                let vacantes = item.capacidad - (item.inscritos || 0);
                disponibilidad = vacantes > 0 ? `${vacantes} ${vacantes === 1 ? 'vacante' : 'vacantes'}` : 'Completo';
                 // TO-DO: Definir acciones para 'asignado' (Cambiar Disponibilidad - redirigir a backend?)
                // Por ahora, un placeholder o link de ejemplo
                 acciones = `<a href="/wp-admin/post.php?post=${item.clase_id}&action=edit" target="_blank" class="button button-small">Gestionar</a>`; // Ejemplo de link a backend
                 // acciones = `<button type="button" class="button button-small msh-change-availability-btn" data-clase-id="${item.clase_id}">Cambiar Capacidad</button>`; // Si se implementara modal frontend

            } else { // 'vacio'
                 let programas = getNamesFromIds(item.programas_admisibles, msh_frontend_data.programa_names).join(', ');
                 let sedes = getNamesFromIds(item.sedes_admisibles, msh_frontend_data.sede_names).join(', ');
                 let rangos = getNamesFromIds(item.rangos_admisibles, msh_frontend_data.rango_names).join(', ');

                 // Acortar para visualización
                 const maxLen = 30;
                 programaDisplay = programas.length > maxLen ? `<span title="${programas}">${programas.substring(0,maxLen)}...</span>` : (programas || '<em>N/A</em>');
                 sedeDisplay = sedes.length > maxLen ? `<span title="${sedes}">${sedes.substring(0,maxLen)}...</span>` : (sedes || '<em>N/A</em>');
                 rangoDisplay = rangos.length > maxLen ? `<span title="${rangos}">${rangos.substring(0,maxLen)}...</span>` : (rangos || '<em>N/A</em>');

                disponibilidad = 'Vacío';
                 // TO-DO: Definir acciones para 'vacio' (Asignar Horario - redirigir a backend?)
                 // Por ahora, un placeholder o link de ejemplo
                 let addClassUrl = `/wp-admin/post-new.php?post_type=msh_clase&maestro_id=${item.maestro_id}&dia=${item.dia}&hora_inicio=${item.hora_inicio}&hora_fin=${item.hora_fin}`;
                 acciones = `<a href="${addClassUrl}" target="_blank" class="button button-primary button-small">Asignar</a>`; // Ejemplo link a backend pre-rellenado
                 // acciones = `<button type="button" class="button button-primary button-small msh-assign-schedule-btn" data-maestro-id="${item.maestro_id}" data-block='${JSON.stringify(item)}'>Asignar Horario</button>`; // Si se implementara modal frontend
            }

            tableHtml += `
                <tr>
                    <td>${horario}</td>
                    <td>${maestro}</td>
                    <td>${programaDisplay}</td>
                    <td>${sedeDisplay}</td>
                    <td>${rangoDisplay}</td>
                    <td>${disponibilidad}</td>
                    <td>${acciones}</td>
                </tr>
            `;
        });

        tableHtml += `
                </tbody>
            </table>
        `;

        // Insertar la tabla en el contenedor
        resultsContainer.html(tableHtml);
    }

    // TO-DO: Añadir listeners para los botones de acción si se implementan modales frontend
    // resultsContainer.on('click', '.msh-change-availability-btn', function() { /* ... abrir modal capacidad ... */ });
    // resultsContainer.on('click', '.msh-assign-schedule-btn', function() { /* ... abrir modal asignación ... */ });

});