// assets/js/admin-script.js
jQuery(document).ready(function($) {

    // ============================================================
    // =                VERIFICACIÓN INICIAL (Admin)              =
    // ============================================================
    if (typeof msh_admin_data === 'undefined' || !msh_admin_data.ajax_url || !msh_admin_data.post_id) {
        console.error('MSH Admin Error: msh_admin_data no está definido o le faltan propiedades esenciales (ajax_url, post_id). Verifica wp_localize_script en includes/admin/assets.php.');
        $('#msh-disponibilidad-manager-container, .msh-clases-container').html('<p style="color:red;">Error crítico: Faltan datos de configuración del plugin (admin).</p>');
        return; // Detener si faltan datos críticos
    }

    // ============================================================
    // =      GESTIÓN DISPONIBILIDAD GENERAL (TABLA / MODAL - ADMIN) =
    // ============================================================

    var availabilityManagerContainer = $('#msh-disponibilidad-manager-container');
    // Solo proceder si el contenedor existe (estamos en la página correcta)
    if (availabilityManagerContainer.length > 0) {
        var availabilityTableBody = $('#msh-availability-list');
        var availabilityModalContainer = $('#msh-availability-modal-container');
        var availabilityModalContent = $('#msh-availability-modal-content');
        var saveAvailabilityButton = $('#msh-save-availability-changes-btn');
        var availabilitySpinner = $('#msh-availability-spinner');
        var availabilitySaveStatus = $('#msh-availability-save-status');

        let currentAvailabilityData = msh_admin_data.availability_initial_data || [];
        let availabilityChangesMade = false;

        // --- Helpers Disponibilidad (Admin) ---
        function getAdminDayName(dayKey) { return (msh_admin_data.days_of_week && msh_admin_data.days_of_week[dayKey]) ? msh_admin_data.days_of_week[dayKey] : dayKey; }
        function getAdminNameById(id, mapName) { const map = msh_admin_data[mapName] || {}; return map[id] || `ID:${id}?`; }
        function getAdminNamesFromIds(ids, mapName) { if (!Array.isArray(ids)) return []; const map = msh_admin_data[mapName] || {}; return ids.map(id => parseInt(id, 10)).filter(numId => !isNaN(numId) && map.hasOwnProperty(numId)).map(numId => map[numId]); }
        function sortAvailabilityCallbackJS(a, b) { const dayOrder = Object.keys(msh_admin_data.days_of_week || {}); const orderA = dayOrder.indexOf(a.dia); const orderB = dayOrder.indexOf(b.dia); if (orderA !== orderB) return (orderA === -1 ? 99 : orderA) - (orderB === -1 ? 99 : orderB); const timeA = a.hora_inicio || '99:99'; const timeB = b.hora_inicio || '99:99'; if (timeA < timeB) return -1; if (timeA > timeB) return 1; return 0; }

        // --- Render Tabla Disponibilidad (Admin) ---
        function renderAvailabilityTable() {
            availabilityTableBody.empty();
            const hasSedeNames = typeof msh_admin_data.sede_names === 'object'; const hasProgramaNames = typeof msh_admin_data.programa_names === 'object'; const hasRangoNames = typeof msh_admin_data.rango_names === 'object';
            var loadingRow = $('#msh-availability-loading-row');
            var noDataRow = $('#msh-no-availability-row');

            if (!currentAvailabilityData || currentAvailabilityData.length === 0) {
                noDataRow.show(); loadingRow.hide();
            } else {
                noDataRow.hide(); loadingRow.hide();
                currentAvailabilityData.sort(sortAvailabilityCallbackJS);
                $.each(currentAvailabilityData, function(index, block) {
                    let sedeNamesArray = getAdminNamesFromIds(block.sedes, 'sede_names'); let programaNamesArray = getAdminNamesFromIds(block.programas, 'programa_names'); let rangoNamesArray = getAdminNamesFromIds(block.rangos, 'rango_names');
                    let sedeDisplay = sedeNamesArray.length > 0 ? sedeNamesArray.join(', ') : '<em>Ninguna</em>'; let programaDisplay = programaNamesArray.length > 0 ? programaNamesArray.join(', ') : '<em>Ninguno</em>'; let rangoDisplay = rangoNamesArray.length > 0 ? rangoNamesArray.join(', ') : '<em>Ninguno</em>';
                    const maxLen = 50;
                    let sedeTitleAttr = sedeNamesArray.join(', '); let programaTitleAttr = programaNamesArray.join(', '); let rangoTitleAttr = rangoNamesArray.join(', ');
                    if (sedeDisplay.length > maxLen) sedeDisplay = sedeDisplay.substring(0, maxLen) + '...'; if (programaDisplay.length > maxLen) programaDisplay = programaDisplay.substring(0, maxLen) + '...'; if (rangoDisplay.length > maxLen) rangoDisplay = rangoDisplay.substring(0, maxLen) + '...';
                    let editButtonText = msh_admin_data.text_modal_edit_button || 'Editar'; let deleteButtonText = msh_admin_data.text_delete_button || 'Eliminar';
                    let rowHtml = `<tr id="msh-availability-row-${index}" data-index="${index}"><td>${getAdminDayName(block.dia)}</td><td>${block.hora_inicio||''} - ${block.hora_fin||''}</td><td title="${sedeTitleAttr}">${sedeDisplay}</td><td title="${programaTitleAttr}">${programaDisplay}</td><td title="${rangoTitleAttr}">${rangoDisplay}</td><td><button type="button" class="button button-small msh-edit-availability" data-index="${index}">${editButtonText}</button> <button type="button" class="button button-small button-link-delete msh-delete-availability" data-index="${index}">${deleteButtonText}</button></td></tr>`;
                    availabilityTableBody.append(rowHtml);
                });
            }
            updateSaveButtonState();
        }

        // --- Update Botón Guardar Disponibilidad (Admin) ---
        function updateSaveButtonState() { saveAvailabilityButton.prop('disabled', !availabilityChangesMade); if (!availabilityChangesMade) availabilitySaveStatus.text(''); }

        // --- Helpers Modal Disponibilidad (Admin) ---
        function openAvailabilityModal(title) {
             var width = $(window).width() * 0.8; var height = $(window).height() * 0.9; if (width > 850) width = 850; if (height > 750) height = 750;
             availabilityModalContainer.hide(); availabilityModalContent.html('<p>'+(msh_admin_data.text_loading_availability_form||'Cargando...')+'</p>');
             tb_show( title, '#TB_inline?width='+width+'&height='+height+'&inlineId=msh-availability-modal-container', null );
             resetAvailabilityModalMessages();
        }
        function closeAvailabilityModal() { availabilityModalContent.empty(); availabilityModalContainer.hide(); tb_remove(); }
        function resetAvailabilityModalMessages() { var form = $('#msh-availability-form'); if (form.length) form.find('#msh-availability-validation-messages').html('').hide(); else availabilityModalContent.find('#msh-availability-validation-messages').remove(); }

        // --- Abrir Modal Añadir Disponibilidad (Admin) ---
        availabilityManagerContainer.on('click', '#msh-add-availability-btn', function(e) {
            e.preventDefault();
            openAvailabilityModal(msh_admin_data.text_modal_title_add_availability || 'Añadir Bloque');
            var nonce = msh_admin_data.manage_availability_nonce; if (!nonce) { availabilityModalContent.html('<p style="color:red;">Nonce Faltante.</p>'); return; }
            $.ajax({
                url: msh_admin_data.ajax_url, type: 'POST', data: { action: 'msh_load_disponibilidad_form', maestro_id: msh_admin_data.post_id, block_index: -1, block_data: JSON.stringify({}), security: nonce },
                success: function(response) { if (response.success) { availabilityModalContent.html(response.data.html); attachAvailabilityModalListeners(); } else { availabilityModalContent.html('<p style="color:red;">' + (response.data.message || 'Error.') + '</p>'); } }, error: function() { availabilityModalContent.html('<p style="color:red;">Error conexión.</p>'); }
            });
        });

        // --- Abrir Modal Editar Disponibilidad (Admin) ---
        availabilityManagerContainer.on('click', '.msh-edit-availability', function(e) {
            e.preventDefault(); var index = $(this).data('index'); if (typeof index === 'undefined' || !currentAvailabilityData[index]) { alert('Error: Bloque no encontrado.'); return; } var blockData = currentAvailabilityData[index];
            openAvailabilityModal(msh_admin_data.text_modal_title_edit_availability || 'Editar Bloque');
            var nonce = msh_admin_data.manage_availability_nonce; if (!nonce) { availabilityModalContent.html('<p style="color:red;">Nonce Faltante.</p>'); return; }
            $.ajax({
                url: msh_admin_data.ajax_url, type: 'POST', data: { action: 'msh_load_disponibilidad_form', maestro_id: msh_admin_data.post_id, block_index: index, block_data: JSON.stringify(blockData), security: nonce },
                success: function(response) { if (response.success) { availabilityModalContent.html(response.data.html); attachAvailabilityModalListeners(); } else { availabilityModalContent.html('<p style="color:red;">' + (response.data.message || 'Error.') + '</p>'); } }, error: function() { availabilityModalContent.html('<p style="color:red;">Error conexión.</p>'); }
            });
        });

        // --- Adjuntar Listeners Modal Disponibilidad (Admin) ---
        function attachAvailabilityModalListeners() {
            var modalForm = $('#msh-availability-form'); if (!modalForm.length) return;
            // Cancelar
            modalForm.find('.msh-cancel-availability-btn').off('click').on('click', function(e) { e.preventDefault(); closeAvailabilityModal(); });
            // Submit (Actualizar Array JS)
            modalForm.off('submit').on('submit', function(e) {
                e.preventDefault(); var form = $(this); var validationDiv = form.find('#msh-availability-validation-messages').html('').hide();
                var blockData = {}; var blockIndex = parseInt(form.find('input[name="block_index"]').val(), 10);
                blockData.dia = form.find('#msh_avail_dia').val(); blockData.hora_inicio = form.find('#msh_avail_hora_inicio').val(); blockData.hora_fin = form.find('#msh_avail_hora_fin').val();
                blockData.sedes = form.find('input[name="msh_avail_sedes[]"]:checked').map(function() { return $(this).val(); }).get(); blockData.programas = form.find('input[name="msh_avail_programas[]"]:checked').map(function() { return $(this).val(); }).get(); blockData.rangos = form.find('input[name="msh_avail_rangos[]"]:checked').map(function() { return $(this).val(); }).get();
                var errors = []; if (!blockData.dia) errors.push('Día obligatorio.'); if (!blockData.hora_inicio) errors.push('Hora inicio obligatoria.'); if (!blockData.hora_fin) errors.push('Hora fin obligatoria.'); if (blockData.hora_inicio && blockData.hora_fin && blockData.hora_fin <= blockData.hora_inicio) errors.push(msh_admin_data.text_validation_end_after_start || 'Fin <= Inicio.');
                var isDuplicate = false; for (var i = 0; i < currentAvailabilityData.length; i++) { if (blockIndex !== -1 && i === blockIndex) continue; if (currentAvailabilityData[i].dia === blockData.dia && currentAvailabilityData[i].hora_inicio === blockData.hora_inicio) { isDuplicate = true; break; } } if (isDuplicate) errors.push(msh_admin_data.text_validation_duplicate_slot || 'Duplicado.');
                if (errors.length > 0) { validationDiv.html(errors.join('<br>')).show(); return; }
                if (blockIndex === -1) { currentAvailabilityData.push(blockData); } else { currentAvailabilityData[blockIndex] = blockData; }
                availabilityChangesMade = true; renderAvailabilityTable(); closeAvailabilityModal();
            });
        }

        // --- Eliminar Bloque Disponibilidad (Admin) ---
        availabilityManagerContainer.on('click', '.msh-delete-availability', function(e) {
            e.preventDefault(); var index = $(this).data('index'); if (typeof index === 'undefined' || !currentAvailabilityData[index]) return;
            var confirmMsg = msh_admin_data.text_confirm_delete_availability || '¿Eliminar?';
            if (confirm(confirmMsg)) { currentAvailabilityData.splice(index, 1); availabilityChangesMade = true; renderAvailabilityTable(); }
        });

        // --- Guardar TODOS los Cambios de Disponibilidad (Admin AJAX) ---
        availabilityManagerContainer.on('click', '#msh-save-availability-changes-btn', function(e) {
            e.preventDefault(); var button = $(this); if (!availabilityChangesMade || button.prop('disabled')) return;
            var nonce = $('#msh_save_disponibilidad_nonce').val(); if (!nonce) { availabilitySaveStatus.text('Nonce error').css('color', 'red'); return; }
            button.prop('disabled', true); availabilitySpinner.addClass('is-active').css('visibility', 'visible'); availabilitySaveStatus.text(msh_admin_data.text_saving_availability || 'Guardando...').css('color', '');
            $.ajax({
                url: msh_admin_data.ajax_url, type: 'POST', data: { action: 'msh_save_disponibilidad', maestro_id: msh_admin_data.post_id, availability_data: JSON.stringify(currentAvailabilityData), security: nonce },
                success: function(response) { if (response.success) { availabilityChangesMade = false; availabilitySaveStatus.text(response.data.message || 'Guardado.').css('color', 'green'); updateSaveButtonState(); } else { availabilitySaveStatus.html((msh_admin_data.text_availability_save_error||'Error:')+'<br>'+(response.data.message||'')).css('color', 'red'); button.prop('disabled', false); } },
                error: function() { availabilitySaveStatus.text(msh_admin_data.modal_error_saving || 'Error conexión.').css('color', 'red'); button.prop('disabled', false); },
                complete: function() { availabilitySpinner.removeClass('is-active').css('visibility', 'hidden'); setTimeout(function(){ availabilitySaveStatus.text(''); }, 5000); }
            });
        });

        // --- Inicialización Disponibilidad Admin ---
        renderAvailabilityTable();
    } // Fin if (availabilityManagerContainer.length > 0)


    // ============================================================
    // =          GESTIÓN DE CLASES PROGRAMADAS (ADMIN)           =
    // ============================================================
    var adminMaestroClasesContainer = $('.msh-clases-container');
    // Solo proceder si el contenedor existe
    if (adminMaestroClasesContainer.length > 0) {
        var adminClaseModalContainer = $('#msh-clase-modal-container'); // Usar ID del modal de admin
        var adminClaseModalContent = $('#msh-clase-modal-content');
        var adminClasesTableBody = $('#msh-clases-list');
        var currentAdminModalAvailability = null; // Disponibilidad para filtros en modal admin

        // --- Helpers Modales Clases (Admin) ---
        function openAdminClaseModal(title) {
            var width = $(window).width() * 0.8; var height = $(window).height() * 0.8; if (width > 800) width = 800; if (height > 650) height = 650;
            adminClaseModalContainer.hide(); adminClaseModalContent.html('<p>'+(msh_admin_data.modal_loading_form||'Cargando...')+'</p>');
            tb_show(title, '#TB_inline?width='+width+'&height='+height+'&inlineId=msh-clase-modal-container', null);
            resetAdminClaseModalMessages();
        }
        function closeAdminClaseModal() { adminClaseModalContent.empty(); adminClaseModalContainer.hide(); tb_remove(); currentAdminModalAvailability = null; }
        function resetAdminClaseModalMessages() { var form = $('#msh-clase-form'); if(form.length){ form.find('#msh-clase-validation-messages, #msh-clase-proximity-warning').html('').hide(); form.find('.spinner').removeClass('is-active'); form.find('#msh-save-clase-btn').prop('disabled', false); } else { adminClaseModalContent.find('#msh-clase-validation-messages, #msh-clase-proximity-warning').remove(); } }

        // --- Abrir Modal Añadir Clase (Admin) ---
        adminMaestroClasesContainer.on('click', '#msh-add-new-clase-btn', function(e) {
            e.preventDefault();
            openAdminClaseModal(msh_admin_data.modal_title_manage_clase || 'Asignar Horario'); // Ajustar título si es necesario
            var nonce = msh_admin_data.manage_clases_nonce; if (!nonce) { adminClaseModalContent.html('<p style="color:red;">Nonce Faltante.</p>'); return; }
            $.ajax({
                url: msh_admin_data.ajax_url, type: 'POST', data: { action: 'msh_load_clase_form', maestro_id: $(this).data('maestro-id'), clase_id: 0, security: nonce },
                success: function(response) { if (response.success) { var formHtml = response.data.html + (msh_admin_data.save_clase_nonce_field || ''); adminClaseModalContent.html(formHtml); attachAdminClaseModalListeners(); if(response.data.maestro_availability) initializeAdminDynamicFiltering(response.data.maestro_availability); } else { adminClaseModalContent.html('<p style="color:red;">' + (response.data.message || 'Error.') + '</p>'); } }, error: function() { adminClaseModalContent.html('<p style="color:red;">Error conexión.</p>'); }
            });
        });

        // --- Abrir Modal Editar Clase (Admin) ---
        adminMaestroClasesContainer.on('click', '.msh-edit-clase', function(e) {
            e.preventDefault(); var claseId = $(this).data('clase-id');
            openAdminClaseModal(msh_admin_data.modal_title_manage_clase || 'Editar Clase');
            var nonce = msh_admin_data.manage_clases_nonce; if (!nonce) { adminClaseModalContent.html('<p style="color:red;">Nonce Faltante.</p>'); return; }
            $.ajax({
                url: msh_admin_data.ajax_url, type: 'POST', data: { action: 'msh_load_clase_form', maestro_id: $('#msh-add-new-clase-btn').data('maestro-id'), clase_id: claseId, security: nonce },
                success: function(response) { if (response.success) { var formHtml = response.data.html + (msh_admin_data.save_clase_nonce_field || ''); adminClaseModalContent.html(formHtml); attachAdminClaseModalListeners(); if(response.data.maestro_availability){ initializeAdminDynamicFiltering(response.data.maestro_availability); $('#msh-clase-form #msh_clase_dia, #msh-clase-form #msh_clase_hora_inicio, #msh-clase-form #msh_clase_hora_fin').trigger('change'); } } else { adminClaseModalContent.html('<p style="color:red;">' + (response.data.message || 'Error.') + '</p>'); } }, error: function() { adminClaseModalContent.html('<p style="color:red;">Error conexión.</p>'); }
            });
        });

         // --- Función Adjuntar Listeners Modal Clases (ADMIN) ---
         function attachAdminClaseModalListeners() {
             var modalForm = $('#msh-clase-form'); if (!modalForm.length) { console.error("Admin modal form not found"); return; }
             // Cancelar
             modalForm.find('.msh-cancel-clase-btn').off('click').on('click', function(e) { e.preventDefault(); closeAdminClaseModal(); });
             // Submit (Guardar)
             modalForm.off('submit').on('submit', function(e) {
                 e.preventDefault(); var form = $(this); var submitButton = form.find('#msh-save-clase-btn'); var spinner = form.find('.spinner'); var validationMsgDiv = form.find('#msh-clase-validation-messages').html('').hide(); var proximityWarningDiv = form.find('#msh-clase-proximity-warning').html('').hide();
                 submitButton.prop('disabled', true); spinner.addClass('is-active').css('visibility', 'visible');
                 var startTime = form.find('#msh_clase_hora_inicio').val(); var endTime = form.find('#msh_clase_hora_fin').val(); if (startTime && endTime && endTime <= startTime) { validationMsgDiv.html(msh_admin_data.validation_end_after_start || 'Fin <= Inicio.').show(); submitButton.prop('disabled', false); spinner.removeClass('is-active').css('visibility', 'hidden'); return; }
                 var saveNonce = form.find('input[name="msh_save_clase_nonce"]').val(); if (!saveNonce) { validationMsgDiv.html('Nonce error').show(); submitButton.prop('disabled', false); spinner.removeClass('is-active').css('visibility', 'hidden'); return; }
                 $.ajax({
                     url: msh_admin_data.ajax_url, type: 'POST', data: form.serialize() + '&action=msh_save_clase&security=' + saveNonce,
                     success: function(response) { if (response.success) { closeAdminClaseModal(); alert(response.data.message || 'Guardado.'); location.reload(); } else { validationMsgDiv.html(response.data.message || 'Error.').show(); submitButton.prop('disabled', false); } },
                     error: function() { validationMsgDiv.html(msh_admin_data.modal_error_saving || 'Error conexión.').show(); submitButton.prop('disabled', false); },
                     complete: function() { spinner.removeClass('is-active').css('visibility', 'hidden'); }
                 });
             });
         }

        // --- Eliminar Clase (Admin) ---
        adminMaestroClasesContainer.on('click', '.msh-delete-clase', function(e) {
            e.preventDefault(); var confirmMsg = msh_admin_data.confirm_delete_clase || '¿Eliminar?'; if (!confirm(confirmMsg)) return;
            var button = $(this); var claseId = button.data('clase-id'); var nonce = msh_admin_data.manage_clases_nonce; var row = $('#msh-clase-row-' + claseId);
            if (!nonce) { alert('Nonce error'); return; } button.prop('disabled', true);
            $.ajax({
                url: msh_admin_data.ajax_url, type: 'POST', data: { action: 'msh_delete_clase', clase_id: claseId, security: nonce },
                success: function(response) { if (response.success) { row.fadeOut(300, function() { $(this).remove(); if (adminClasesTableBody.find('tr:not(#msh-no-clases-row)').length === 0) { $('#msh-no-clases-row').show(); } }); /* admin notice? */ } else { alert('Error: ' + (response.data.message || '')); button.prop('disabled', false); } },
                error: function() { alert('Error conexión'); button.prop('disabled', false); }
            });
        });

        // --- Filtrado Dinámico (ADMIN) ---
        function initializeAdminDynamicFiltering(availabilityData) {
            var form = $('#msh-clase-form'); if (!form.length) return; currentAdminModalAvailability = availabilityData;
            form.off('change', '#msh_clase_dia, #msh_clase_hora_inicio, #msh_clase_hora_fin').on('change', '#msh_clase_dia, #msh_clase_hora_inicio, #msh_clase_hora_fin', checkAdminAvailabilityAndUpdateDropdowns);
        }
        function checkAdminAvailabilityAndUpdateDropdowns() {
            var form = $('#msh-clase-form'); if (!form.length || !currentAdminModalAvailability) return;
            var diaSelect = form.find('#msh_clase_dia'); var horaInicioInput = form.find('#msh_clase_hora_inicio'); var horaFinInput = form.find('#msh_clase_hora_fin');
            var sedeSelect = form.find('#msh_clase_sede_id'); var programaSelect = form.find('#msh_clase_programa_id'); var rangoSelect = form.find('#msh_clase_rango_id');
            var hints = form.find('.msh-availability-hint').hide(); var selectedDia = diaSelect.val(); var selectedHoraInicio = horaInicioInput.val(); var selectedHoraFin = horaFinInput.val();
            var hintText = msh_admin_data.availability_hint_text || 'No disponible/admisible.';

            sedeSelect.find('option').data('admisible', false); programaSelect.find('option').data('admisible', false); rangoSelect.find('option').data('admisible', false);

            if (!selectedDia || !selectedHoraInicio || !selectedHoraFin || selectedHoraFin <= selectedHoraInicio) { sedeSelect.find('option').prop('disabled', false).show(); programaSelect.find('option').prop('disabled', false).show(); rangoSelect.find('option').prop('disabled', false).show(); return; }
            var timeStart = selectedHoraInicio; var timeEnd = selectedHoraFin; var foundBlock = null; var admissibleSedes = []; var admissibleProgramas = []; var admissibleRangos = [];

            if (Array.isArray(currentAdminModalAvailability)) { for (var i = 0; i < currentAdminModalAvailability.length; i++) { var block = currentAdminModalAvailability[i]; if (block && block.dia === selectedDia && block.hora_inicio && block.hora_fin && timeStart >= block.hora_inicio && timeEnd <= block.hora_fin) { foundBlock = block; admissibleSedes = Array.isArray(block.sedes) ? block.sedes : []; admissibleProgramas = Array.isArray(block.programas) ? block.programas : []; admissibleRangos = Array.isArray(block.rangos) ? block.rangos : []; break; } } }

            updateAdminSelectOptions(sedeSelect, admissibleSedes, foundBlock, hintText); updateAdminSelectOptions(programaSelect, admissibleProgramas, foundBlock, hintText); updateAdminSelectOptions(rangoSelect, admissibleRangos, foundBlock, hintText);
        }
        function updateAdminSelectOptions(selectElement, admissibleIds, foundBlock, hintText) {
            if (!selectElement.length) return; var currentSelection = selectElement.val(); selectElement.find('option:not(:first-child)').prop('disabled', false).show();
            selectElement.find('option:not(:first-child)').each(function() { var option = $(this); var optionId = parseInt(option.val(), 10); var isAdmissible = admissibleIds.map(Number).includes(optionId); option.data('admisible', isAdmissible); if (!isAdmissible) option.prop('disabled', true).hide(); });
            var selectedOption = selectElement.find('option[value="' + currentSelection + '"]'); if (currentSelection !== "" && selectedOption.length > 0 && selectedOption.prop('disabled')) { selectElement.val(""); }
            var hint = selectElement.closest('td').find('.msh-availability-hint'); if (!foundBlock) { selectElement.find('option:not(:first-child)').prop('disabled', true).hide(); selectElement.val(""); hint.html(hintText).show(); } else if (selectElement.val() === "" && currentSelection !== "") { hint.html(hintText).show(); } else { hint.hide(); }
        }

    } // Fin if (adminMaestroClasesContainer.length > 0)

}); // Fin jQuery Ready