// assets/js/frontend-script.js
jQuery(document).ready(function($) {

    // === Verificación Inicial ===
    if (typeof msh_frontend_data === 'undefined' || !msh_frontend_data.ajax_url || !msh_frontend_data.nonce) {
        console.error('MSH Frontend Error: Faltan datos de configuración.');
        $('#msh-schedule-results-feedback').text('Error de configuración.').show();
        return;
    }

    // === Selectores ===
    var filterForm = $('#msh-schedule-filters-form');
    var resultsContainer = $('#msh-schedule-results-table-container');
    var feedbackDiv = $('#msh-schedule-results-feedback');
    var initialMessage = $('#msh-initial-message');
    var frontendModalContainer = $('#msh-frontend-clase-modal-container'); // ID del contenedor en el shortcode
    var frontendModalContent = $('#msh-frontend-clase-modal-content'); // ID del contenido dentro del contenedor

    // === Helpers ===
    function getDayName(dayKey) { return (msh_frontend_data.days_of_week && msh_frontend_data.days_of_week[dayKey]) ? msh_frontend_data.days_of_week[dayKey] : dayKey; }
    function getNameById(id, mapName) { const map = msh_frontend_data[mapName] || {}; return map[id] || `ID:${id}?`; }
    function getNamesFromIds(ids, mapName) {
        if (!Array.isArray(ids)) return []; const map = msh_frontend_data[mapName] || {};
        return ids.map(id => parseInt(id, 10)).filter(numId => !isNaN(numId) && map.hasOwnProperty(numId)).map(numId => map[numId]);
    }

    // === Lógica Filtros ===
    filterForm.on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize(); var submitButton = $(this).find('.msh-filter-submit-btn');
        var dataToSend = formData + '&action=msh_filter_schedule&nonce=' + msh_frontend_data.nonce;

        $.ajax({
            url: msh_frontend_data.ajax_url, type: 'POST', data: dataToSend,
            beforeSend: function() {
                submitButton.prop('disabled', true); initialMessage.hide();
                feedbackDiv.text(msh_frontend_data.loading_message || 'Buscando...').removeClass('msh-error msh-info').addClass('msh-loading').show();
                resultsContainer.empty().append('<div class="msh-spinner"></div>');
            },
            success: function(response) {
                feedbackDiv.hide().removeClass('msh-loading');
                if (response.success) {
                    renderResultsTable(response.data.schedule_data);
                    if (!response.data.schedule_data || response.data.schedule_data.length === 0) {
                         feedbackDiv.text(msh_frontend_data.no_results_message || 'No hay resultados.').addClass('msh-info').show();
                    }
                } else {
                    var errorMsg = response.data.message || msh_frontend_data.error_message || 'Error.';
                    feedbackDiv.text(errorMsg).addClass('msh-error').show(); resultsContainer.empty();
                }
            },
            error: function() {
                 feedbackDiv.text(msh_frontend_data.error_message || 'Error.').addClass('msh-error').show(); resultsContainer.empty();
            },
            complete: function() { submitButton.prop('disabled', false); resultsContainer.find('.msh-spinner').remove(); }
        });
    });

    filterForm.on('reset', function() {
        resultsContainer.empty(); feedbackDiv.hide().text('').removeClass('msh-error msh-loading msh-info'); initialMessage.show();
    });

    // === Renderizado Tabla ===
    function renderResultsTable(scheduleData) {
        resultsContainer.empty();
        if (!scheduleData || scheduleData.length === 0) { return; }

        var tableHtml = `<table class="msh-results-table"><thead><tr><th>Horario</th><th>Maestro</th><th>Programa</th><th>Sede</th><th>Rango de Edades</th><th>Disponibilidad</th><th>Acciones</th></tr></thead><tbody>`;
        const maxLen = 30;

        $.each(scheduleData, function(index, item) {
            let horario = `${getDayName(item.dia)} ${item.hora_inicio}-${item.hora_fin}`;
            let maestro = getNameById(item.maestro_id, 'maestro_names');
            let programaDisplay, sedeDisplay, rangoDisplay, disponibilidad, acciones;

            if (item.type === 'asignado') {
                programaDisplay = getNameById(item.programa_id, 'programa_names'); sedeDisplay = getNameById(item.sede_id, 'sede_names'); rangoDisplay = getNameById(item.rango_id, 'rango_names');
                let vacantes = item.capacidad - (item.inscritos || 0); disponibilidad = vacantes > 0 ? `${vacantes} ${vacantes === 1 ? 'vacante' : 'vacantes'}` : 'Completo';
                acciones = `<button type="button" class="button button-small msh-manage-assigned-btn" data-clase-id="${item.clase_id}" data-maestro-id="${item.maestro_id}">Gestionar</button>`;
            } else { // 'vacio'
                 let programasAdmisiblesNombres = getNamesFromIds(item.programas_admisibles, 'programa_names'); let sedesAdmisiblesNombres = getNamesFromIds(item.sedes_admisibles, 'sede_names'); let rangosAdmisiblesNombres = getNamesFromIds(item.rangos_admisibles, 'rango_names');
                 let programasStr = programasAdmisiblesNombres.join(', '); let sedesStr = sedesAdmisiblesNombres.join(', '); let rangosStr = rangosAdmisiblesNombres.join(', ');
                 programaDisplay = programasStr ? (programasStr.length > maxLen ? `<span title="${programasStr}">${programasStr.substring(0,maxLen)}...</span>` : programasStr) : '<em>N/A</em>';
                 sedeDisplay = sedesStr ? (sedesStr.length > maxLen ? `<span title="${sedesStr}">${sedesStr.substring(0,maxLen)}...</span>` : sedesStr) : '<em>N/A</em>';
                 rangoDisplay = rangosStr ? (rangosStr.length > maxLen ? `<span title="${rangosStr}">${rangosStr.substring(0,maxLen)}...</span>` : rangosStr) : '<em>N/A</em>';
                 disponibilidad = 'Vacío';
                 acciones = `<button type="button" class="button button-primary button-small msh-assign-empty-btn" data-maestro-id="${item.maestro_id}" data-block-details='${JSON.stringify(item)}'>Asignar</button>`;
            }
            tableHtml += `<tr><td>${horario}</td><td>${maestro}</td><td>${programaDisplay}</td><td>${sedeDisplay}</td><td>${rangoDisplay}</td><td>${disponibilidad}</td><td>${acciones}</td></tr>`;
        });
        tableHtml += `</tbody></table>`;
        resultsContainer.html(tableHtml);
    }

    // === Lógica Modales Frontend ===
    function openFrontendModal(modalId, contentId, title) {
         var width = $(window).width() * 0.8; var height = $(window).height() * 0.8; if (width > 800) width = 800; if (height > 650) height = 650;
         var $modalContainer = $('#' + modalId); var $modalContent = $('#' + contentId);
         $modalContainer.hide(); $modalContent.html('<p>' + (msh_frontend_data.modal_loading_form || 'Cargando...') + '</p>');

         // Mostrar ThickBox
         tb_show( title, '#TB_inline?width=' + width + '&height=' + height + '&inlineId=' + modalId, null );

         // *** Hacer el modal de ThickBox arrastrable DESPUÉS de que se muestre ***
         // Usar un pequeño retraso para asegurar que TB_window existe
         setTimeout(function() {
            var $tbWindow = $('#TB_window');
            if ($tbWindow.length && $.fn.draggable) { // Verificar que draggable está cargado
                // Hacer arrastrable por la barra de título (#TB_title)
                $tbWindow.draggable({
                    handle: "#TB_title",
                    containment: "window" // Opcional: limitar arrastre a la ventana
                });
                // Añadir cursor de movimiento a la barra de título
                $('#TB_title').css('cursor', 'move');
            } else {
                console.warn("MSH Frontend: jQuery UI Draggable no está disponible o #TB_window no encontrado a tiempo.");
            }
        }, 100); // Retraso de 100ms (ajustar si es necesario)

         resetFrontendClaseModalMessages();
    }

    function closeFrontendModal(modalId, contentId) {
        $('#' + contentId).empty(); $('#' + modalId).hide(); tb_remove();
    }

    function resetFrontendClaseModalMessages() { /* ... como antes ... */ }

    // --- Listener Botón "Gestionar" (Tabla Frontend) ---
    resultsContainer.on('click', '.msh-manage-assigned-btn', function(e) {
        e.preventDefault();
        var button = $(this); var claseId = button.data('clase-id'); var maestroId = button.data('maestro-id');
        var nonce = msh_frontend_data.manage_clases_nonce;
        if (!maestroId || !claseId || !nonce) { alert('Error: Datos/Nonce faltantes.'); return; }
        openFrontendModal('msh-frontend-clase-modal-container', 'msh-frontend-clase-modal-content', msh_frontend_data.modal_title_manage_clase || 'Editar Clase');
        $.ajax({
            url: msh_frontend_data.ajax_url, type: 'POST',
            data: { action: 'msh_load_clase_form', maestro_id: maestroId, clase_id: claseId, security: nonce },
            success: function(response) {
                if (response.success) {


                    // *** Añadir nonce al HTML antes de mostrarlo ***
                    var formHtmlWithNonce = response.data.html;
                    // Buscar la etiqueta de cierre </form> e insertar el nonce antes
                    var closingFormTag = '</form>';
                    if (formHtmlWithNonce.includes(closingFormTag) && msh_frontend_data.save_clase_nonce_field) {
                         formHtmlWithNonce = formHtmlWithNonce.replace(closingFormTag, msh_frontend_data.save_clase_nonce_field + closingFormTag);
                    } else {
                        console.warn("MSH Frontend: No se pudo inyectar el nonce de guardado en el formulario modal.");
                    }
                    frontendModalContent.html(formHtmlWithNonce);
                    // *** Fin añadir nonce ***


                    attachFrontendClaseModalListeners();
                    if (response.data.maestro_availability) {
                        initializeFrontendDynamicFiltering(response.data.maestro_availability);
                        $('#msh-clase-form').find('#msh_clase_dia, #msh_clase_hora_inicio, #msh_clase_hora_fin').trigger('change');
                    }
                } else { frontendModalContent.html('<p style="color:red;">' + (response.data.message || 'Error.') + '</p>'); }
            },
            error: function() { frontendModalContent.html('<p style="color:red;">' + (msh_frontend_data.modal_error_loading || 'Error.') + '</p>'); }
        });
    });

    // --- Listener Botón "Asignar" (Tabla Frontend) ---
    resultsContainer.on('click', '.msh-assign-empty-btn', function(e) {
         e.preventDefault();
         var button = $(this); var maestroId = button.data('maestro-id'); var blockDetails = button.data('block-details');
         var nonce = msh_frontend_data.manage_clases_nonce; var prefillData = {};
         try { prefillData = (typeof blockDetails === 'string') ? JSON.parse(blockDetails) : blockDetails || {}; }
         catch (err) { console.error("Error parsing block details:", err); alert("Error interno."); return; }
         if (!maestroId || !nonce) { alert('Error: Datos/Nonce faltantes.'); return; }
         openFrontendModal('msh-frontend-clase-modal-container', 'msh-frontend-clase-modal-content', msh_frontend_data.modal_title_assign_clase || 'Asignar Horario');
         $.ajax({
            url: msh_frontend_data.ajax_url, type: 'POST',
            data: { action: 'msh_load_clase_form', maestro_id: maestroId, clase_id: 0, security: nonce },
             success: function(response) {
                if (response.success) {

                    // *** Añadir nonce al HTML antes de mostrarlo ***
                    var formHtmlWithNonce = response.data.html;
                    var closingFormTag = '</form>';
                     if (formHtmlWithNonce.includes(closingFormTag) && msh_frontend_data.save_clase_nonce_field) {
                         formHtmlWithNonce = formHtmlWithNonce.replace(closingFormTag, msh_frontend_data.save_clase_nonce_field + closingFormTag);
                     } else {
                         console.warn("MSH Frontend: No se pudo inyectar el nonce de guardado en el formulario modal.");
                     }
                    frontendModalContent.html(formHtmlWithNonce);
                     // *** Fin añadir nonce ***

                    var form = $('#msh-clase-form');
                    if (form.length && prefillData) { // Pre-rellenar
                        if (prefillData.dia) form.find('#msh_clase_dia').val(prefillData.dia);
                        if (prefillData.hora_inicio) form.find('#msh_clase_hora_inicio').val(prefillData.hora_inicio);
                    }
                    attachFrontendClaseModalListeners();
                    if (response.data.maestro_availability) {
                        initializeFrontendDynamicFiltering(response.data.maestro_availability);
                        if (form.length) form.find('#msh_clase_dia, #msh_clase_hora_inicio, #msh_clase_hora_fin').trigger('change');
                    }
                } else { frontendModalContent.html('<p style="color:red;">' + (response.data.message || 'Error.') + '</p>'); }
            },
            error: function() { frontendModalContent.html('<p style="color:red;">' + (msh_frontend_data.modal_error_loading || 'Error.') + '</p>'); }
        });
    });

    // --- Función Adjuntar Listeners Modal Clases (Frontend) ---
    function attachFrontendClaseModalListeners() {
         var modalForm = $('#msh-clase-form'); if (!modalForm.length) { console.error("Form #msh-clase-form not found in modal."); return; }
         // Botón Cancelar
         modalForm.find('.msh-cancel-clase-btn').off('click').on('click', function(e) { e.preventDefault(); closeFrontendModal('msh-frontend-clase-modal-container','msh-frontend-clase-modal-content'); });
         // Submit
         modalForm.off('submit').on('submit', function(e) {
             e.preventDefault();
             var form = $(this); var submitButton = form.find('#msh-save-clase-btn'); var spinner = form.find('.spinner');
             var validationMsgDiv = form.find('#msh-clase-validation-messages').html('').hide(); var proximityWarningDiv = form.find('#msh-clase-proximity-warning').html('').hide();
             submitButton.prop('disabled', true); spinner.addClass('is-active');
             var startTime = form.find('#msh_clase_hora_inicio').val(); var endTime = form.find('#msh_clase_hora_fin').val();
             if (startTime && endTime && endTime <= startTime) { validationMsgDiv.html(msh_frontend_data.validation_end_after_start || 'Hora fin debe ser posterior.').show(); submitButton.prop('disabled', false); spinner.removeClass('is-active'); return; }
             
             var saveNonce = form.find('input[name="msh_save_clase_nonce"]').val(); // Buscar por nombre
             if (!saveNonce) {
                 saveNonce = form.find('#msh_save_clase_nonce').val(); // Buscar por ID si falla el nombre
             }


             if (!saveNonce) { 
                validationMsgDiv.html('Error: Falta Nonce de guardado en el formulario.').show(); 
                submitButton.prop('disabled', false); spinner.removeClass('is-active'); return; }

             $.ajax({
                 url: msh_frontend_data.ajax_url, type: 'POST', data: form.serialize() + '&action=msh_save_clase&security=' + saveNonce,
                 success: function(response) {
                     if (response.success) {
                         closeFrontendModal('msh-frontend-clase-modal-container','msh-frontend-clase-modal-content');
                         alert(response.data.message || 'Guardado.');
                         filterForm.trigger('submit'); // Refrescar tabla
                     } else { validationMsgDiv.html(response.data.message || 'Error.').show(); submitButton.prop('disabled', false); }
                 },
                 error: function() { validationMsgDiv.html(msh_frontend_data.modal_error_saving || 'Error.').show(); submitButton.prop('disabled', false); },
                 complete: function() { spinner.removeClass('is-active'); }
             });
         });
    }

    // --- Filtrado Dinámico y helpers (Frontend) ---
    var currentFrontendModalAvailability = null; // Variable para guardar disponibilidad del modal actual
    function initializeFrontendDynamicFiltering(availabilityData) {
        var form = $('#msh-clase-form'); if (!form.length) return;
        currentFrontendModalAvailability = availabilityData; // Guardar para usar en el handler
        form.off('change', '#msh_clase_dia, #msh_clase_hora_inicio, #msh_clase_hora_fin') // Asegurar quitar listeners viejos
            .on('change', '#msh_clase_dia, #msh_clase_hora_inicio, #msh_clase_hora_fin', checkFrontendAvailabilityAndUpdateDropdowns);
    }

     function checkFrontendAvailabilityAndUpdateDropdowns() {
         var form = $('#msh-clase-form'); if (!form.length) return;
         if (!currentFrontendModalAvailability) { console.warn("checkFrontendAvailability: Faltan datos de disponibilidad."); return; } // Salir si no hay datos
         var diaSelect = form.find('#msh_clase_dia'); var horaInicioInput = form.find('#msh_clase_hora_inicio'); var horaFinInput = form.find('#msh_clase_hora_fin');
         var sedeSelect = form.find('#msh_clase_sede_id'); var programaSelect = form.find('#msh_clase_programa_id'); var rangoSelect = form.find('#msh_clase_rango_id');
         var hints = form.find('.msh-availability-hint'); var selectedDia = diaSelect.val(); var selectedHoraInicio = horaInicioInput.val(); var selectedHoraFin = horaFinInput.val();
         var hintText = msh_frontend_data.availability_hint_text || 'No disponible/admisible.';

         hints.hide();
         sedeSelect.find('option').data('admisible', false); programaSelect.find('option').data('admisible', false); rangoSelect.find('option').data('admisible', false);

         if (!selectedDia || !selectedHoraInicio || !selectedHoraFin || selectedHoraFin <= selectedHoraInicio) {
             sedeSelect.find('option').prop('disabled', false).show(); programaSelect.find('option').prop('disabled', false).show(); rangoSelect.find('option').prop('disabled', false).show(); return;
         }
         var timeStart = selectedHoraInicio; var timeEnd = selectedHoraFin; var foundBlock = null; var admissibleSedes = []; var admissibleProgramas = []; var admissibleRangos = [];

         if (Array.isArray(currentFrontendModalAvailability)) {
             for (var i = 0; i < currentFrontendModalAvailability.length; i++) {
                 var block = currentFrontendModalAvailability[i];
                 if (block && block.dia === selectedDia && block.hora_inicio && block.hora_fin && timeStart >= block.hora_inicio && timeEnd <= block.hora_fin) {
                     foundBlock = block; admissibleSedes = Array.isArray(block.sedes) ? block.sedes : []; admissibleProgramas = Array.isArray(block.programas) ? block.programas : []; admissibleRangos = Array.isArray(block.rangos) ? block.rangos : []; break;
                 }
             }
         }
         updateFrontendSelectOptions(sedeSelect, admissibleSedes, foundBlock, hintText);
         updateFrontendSelectOptions(programaSelect, admissibleProgramas, foundBlock, hintText);
         updateFrontendSelectOptions(rangoSelect, admissibleRangos, foundBlock, hintText);
     }

     function updateFrontendSelectOptions(selectElement, admissibleIds, foundBlock, hintText) {
         if (!selectElement.length) return; var currentSelection = selectElement.val();
         selectElement.find('option:not(:first-child)').prop('disabled', false).show();
         selectElement.find('option:not(:first-child)').each(function() {
             var option = $(this); var optionId = parseInt(option.val(), 10); var isAdmissible = admissibleIds.map(Number).includes(optionId);
             option.data('admisible', isAdmissible); if (!isAdmissible) { option.prop('disabled', true).hide(); }
         });
         var selectedOption = selectElement.find('option[value="' + currentSelection + '"]');
         if (currentSelection !== "" && selectedOption.length > 0 && selectedOption.prop('disabled')) { selectElement.val(""); } // Check if selectedOption exists
         var hint = selectElement.closest('td').find('.msh-availability-hint');
         if (!foundBlock) { selectElement.find('option:not(:first-child)').prop('disabled', true).hide(); selectElement.val(""); hint.html(hintText).show(); }
         else if (selectElement.val() === "" && currentSelection !== "") { hint.html(hintText).show(); }
         else { hint.hide(); }
     }

}); // Fin jQuery Ready