// assets/js/admin-script.js
jQuery(document).ready(function($) {

    // ============================================================
    // =                VERIFICACIÓN INICIAL                     =
    // ============================================================

    // Asegurarse que msh_admin_data exista y tenga las propiedades esenciales
    if (typeof msh_admin_data === 'undefined' || !msh_admin_data.ajax_url || !msh_admin_data.post_id) {
        console.error('MSH Error: msh_admin_data no está definido o le faltan propiedades esenciales (ajax_url, post_id). Verifica wp_localize_script en assets.php.');
        // Podrías mostrar un mensaje de error al usuario en la página si lo deseas
        $('#msh-disponibilidad-manager-container, .msh-clases-container').html('<p style="color:red;">Error crítico: Faltan datos de configuración del plugin.</p>');
        return; // Detener la ejecución si faltan datos críticos
    }

    // ============================================================
    // =           VARIABLES Y HELPERS GLOBALES (Dentro de ready) =
    // ============================================================

    // --- Contenedores principales ---
    var availabilityManagerContainer = $('#msh-disponibilidad-manager-container');
    var maestroClasesContainer = $('.msh-clases-container');

    // --- Disponibilidad General ---
    var availabilityTableBody = $('#msh-availability-list');
    var availabilityModalContainer = $('#msh-availability-modal-container');
    var availabilityModalContent = $('#msh-availability-modal-content');
    var saveAvailabilityButton = $('#msh-save-availability-changes-btn');
    var availabilitySpinner = $('#msh-availability-spinner');
    var availabilitySaveStatus = $('#msh-availability-save-status');
    let currentAvailabilityData = msh_admin_data.availability_initial_data || [];
    let availabilityChangesMade = false;

    // --- Clases Programadas ---
    var clasesModalContainer = $('#msh-clase-modal-container');
    var clasesModalContent = $('#msh-clase-modal-content');
    var clasesTableBody = $('#msh-clases-list');
    let clasesMaestroAvailabilityData = null; // Disponibilidad específica para el modal de clases

     // --- Helper: Obtener nombre del día ---
     function getDayName(dayKey) {
         return (msh_admin_data.days_of_week && msh_admin_data.days_of_week[dayKey]) ? msh_admin_data.days_of_week[dayKey] : dayKey;
     }

    // ============================================================
    // =      GESTIÓN DISPONIBILIDAD GENERAL (TABLA / MODAL)      =
    // ============================================================

    // --- Función de comparación JS para ordenar disponibilidad ---
     function sortAvailabilityCallbackJS(a, b) {
        const dayOrder = Object.keys(msh_admin_data.days_of_week || {});
        const orderA = dayOrder.indexOf(a.dia);
        const orderB = dayOrder.indexOf(b.dia);

        if (orderA !== orderB) {
            return (orderA === -1 ? 99 : orderA) - (orderB === -1 ? 99 : orderB);
        }
        const timeA = a.hora_inicio || '99:99';
        const timeB = b.hora_inicio || '99:99';
        if (timeA < timeB) return -1;
        if (timeA > timeB) return 1;
        return 0;
    }

    // --- Función para Renderizar la Tabla de Disponibilidad ---
    function renderAvailabilityTable() {
        availabilityTableBody.empty();
        const hasSedeNames = typeof msh_admin_data.sede_names === 'object' && msh_admin_data.sede_names !== null;
        const hasProgramaNames = typeof msh_admin_data.programa_names === 'object' && msh_admin_data.programa_names !== null;
        const hasRangoNames = typeof msh_admin_data.rango_names === 'object' && msh_admin_data.rango_names !== null;

        if (!currentAvailabilityData || currentAvailabilityData.length === 0) {
            $('#msh-no-availability-row').show();
            $('#msh-availability-loading-row').hide();
        } else {
            $('#msh-no-availability-row').hide();
            $('#msh-availability-loading-row').hide();
            currentAvailabilityData.sort(sortAvailabilityCallbackJS);

            $.each(currentAvailabilityData, function(index, block) {
                let sedeNamesArray = Array.isArray(block.sedes) ? block.sedes.map(id => (hasSedeNames && msh_admin_data.sede_names[id]) || `ID:${id}?`).filter(name => name) : [];
                let programaNamesArray = Array.isArray(block.programas) ? block.programas.map(id => (hasProgramaNames && msh_admin_data.programa_names[id]) || `ID:${id}?`).filter(name => name) : [];
                let rangoNamesArray = Array.isArray(block.rangos) ? block.rangos.map(id => (hasRangoNames && msh_admin_data.rango_names[id]) || `ID:${id}?`).filter(name => name) : [];

                let sedeDisplay = sedeNamesArray.length > 0 ? sedeNamesArray.join(', ') : '<em>Ninguna</em>';
                let programaDisplay = programaNamesArray.length > 0 ? programaNamesArray.join(', ') : '<em>Ninguno</em>';
                let rangoDisplay = rangoNamesArray.length > 0 ? rangoNamesArray.join(', ') : '<em>Ninguno</em>';

                const maxDisplayLength = 50;
                let sedeTitleAttr = sedeNamesArray.join(', ');
                let programaTitleAttr = programaNamesArray.join(', ');
                let rangoTitleAttr = rangoNamesArray.join(', ');

                if (sedeDisplay.length > maxDisplayLength) sedeDisplay = sedeDisplay.substring(0, maxDisplayLength) + '...';
                if (programaDisplay.length > maxDisplayLength) programaDisplay = programaDisplay.substring(0, maxDisplayLength) + '...';
                if (rangoDisplay.length > maxDisplayLength) rangoDisplay = rangoDisplay.substring(0, maxDisplayLength) + '...';

                // Botones (usando textos localizados con fallbacks)
                let editButtonText = msh_admin_data.text_modal_edit_button || 'Editar';
                let deleteButtonText = msh_admin_data.text_delete_button || 'Eliminar'; // Asumiendo que tienes 'text_delete_button'

                let rowHtml = `
                    <tr id="msh-availability-row-${index}" data-index="${index}">
                        <td>${getDayName(block.dia)}</td>
                        <td>${block.hora_inicio || ''} - ${block.hora_fin || ''}</td>
                        <td title="${sedeTitleAttr}">${sedeDisplay}</td>
                        <td title="${programaTitleAttr}">${programaDisplay}</td>
                        <td title="${rangoTitleAttr}">${rangoDisplay}</td>
                        <td>
                            <button type="button" class="button button-small msh-edit-availability" data-index="${index}">${editButtonText}</button>
                            <button type="button" class="button button-small button-link-delete msh-delete-availability" data-index="${index}">${deleteButtonText}</button>
                        </td>
                    </tr>
                `;
                availabilityTableBody.append(rowHtml);
            });
        }
        updateSaveButtonState();
    }

    // --- Función para Actualizar Estado del Botón Guardar Disponibilidad ---
    function updateSaveButtonState() {
        saveAvailabilityButton.prop('disabled', !availabilityChangesMade);
        if (!availabilityChangesMade) {
            availabilitySaveStatus.text(''); // Limpiar estado si no hay cambios
        }
    }

    // --- Helpers para Modal de Disponibilidad ---
    function openAvailabilityModal(title) {
        var width = $(window).width() * 0.8;
        var height = $(window).height() * 0.9;
        if (width > 850) width = 850;
        if (height > 750) height = 750;

        availabilityModalContainer.hide(); // Ocultar primero
        availabilityModalContent.html('<p>' + (msh_admin_data.text_loading_availability_form || 'Cargando...') + '</p>');

        tb_show(
            title || 'Gestionar Disponibilidad',
            '#TB_inline?width=' + width + '&height=' + height + '&inlineId=msh-availability-modal-container',
            null
        );
        // tb_show hará visible el contenedor por su ID en el href
        resetAvailabilityModalMessages();
    }

    function closeAvailabilityModal() {
        availabilityModalContent.empty(); // Vaciar contenido
        availabilityModalContainer.hide(); // Ocultar contenedor
        tb_remove(); // Cerrar ThickBox
    }

    function resetAvailabilityModalMessages() {
         var form = $('#msh-availability-form');
         if (form.length) {
             form.find('#msh-availability-validation-messages').html('').hide();
         } else {
             // Si el form no existe, intentar limpiar directamente en el contenedor
             availabilityModalContent.find('#msh-availability-validation-messages').remove();
         }
     }

    // --- Abrir Modal Añadir Disponibilidad ---
    availabilityManagerContainer.on('click', '#msh-add-availability-btn', function(e) {
        e.preventDefault();
        openAvailabilityModal(msh_admin_data.text_modal_title_add_availability || 'Añadir Bloque');
        var nonce = msh_admin_data.manage_availability_nonce;
        if (!nonce) { availabilityModalContent.html('<p style="color:red;">Error: Nonce Faltante.</p>'); return; }

        $.ajax({
            url: msh_admin_data.ajax_url, type: 'POST',
            data: {
                action: 'msh_load_disponibilidad_form',
                maestro_id: msh_admin_data.post_id,
                block_index: -1, block_data: JSON.stringify({}), security: nonce
            },
            success: function(response) {
                if (response.success) {
                    availabilityModalContent.html(response.data.html);
                    attachAvailabilityModalListeners(); // Adjuntar listeners al nuevo contenido
                } else { availabilityModalContent.html('<p style="color:red;">' + (response.data.message || 'Error.') + '</p>'); }
            },
            error: function() { availabilityModalContent.html('<p style="color:red;">' + (msh_admin_data.modal_error_loading || 'Error de conexión.') + '</p>'); }
        });
    });

    // --- Abrir Modal Editar Disponibilidad ---
    availabilityManagerContainer.on('click', '.msh-edit-availability', function(e) {
        e.preventDefault();
        var index = $(this).data('index');
        if (typeof index === 'undefined' || !currentAvailabilityData[index]) { alert('Error: Bloque no encontrado.'); return; }
        var blockData = currentAvailabilityData[index];
        openAvailabilityModal(msh_admin_data.text_modal_title_edit_availability || 'Editar Bloque');
        var nonce = msh_admin_data.manage_availability_nonce;
        if (!nonce) { availabilityModalContent.html('<p style="color:red;">Error: Nonce Faltante.</p>'); return; }

        $.ajax({
            url: msh_admin_data.ajax_url, type: 'POST',
            data: {
                action: 'msh_load_disponibilidad_form',
                maestro_id: msh_admin_data.post_id,
                block_index: index, block_data: JSON.stringify(blockData), security: nonce
            },
            success: function(response) {
                if (response.success) {
                    availabilityModalContent.html(response.data.html);
                    attachAvailabilityModalListeners(); // Adjuntar listeners al nuevo contenido
                } else { availabilityModalContent.html('<p style="color:red;">' + (response.data.message || 'Error.') + '</p>'); }
            },
            error: function() { availabilityModalContent.html('<p style="color:red;">' + (msh_admin_data.modal_error_loading || 'Error de conexión.') + '</p>'); }
        });
    });

    // --- Función para Adjuntar Listeners al Modal de Disponibilidad ---
    function attachAvailabilityModalListeners() {
        var modalForm = $('#msh-availability-form');
        if (!modalForm.length) return; // Salir si el form no está

        // Botón Cancelar (usar .one() o .off().on() para seguridad)
        modalForm.find('.msh-cancel-availability-btn').off('click').on('click', function(e) {
            e.preventDefault();
            closeAvailabilityModal();
        });

        // Submit del Formulario (Añadir/Actualizar en array JS)
        modalForm.off('submit').on('submit', function(e) {
             e.preventDefault();
             var form = $(this);
             var validationDiv = form.find('#msh-availability-validation-messages').html('').hide();
             var blockData = {};
             var blockIndex = parseInt(form.find('input[name="block_index"]').val(), 10);

             blockData.dia = form.find('#msh_avail_dia').val();
             blockData.hora_inicio = form.find('#msh_avail_hora_inicio').val();
             blockData.hora_fin = form.find('#msh_avail_hora_fin').val();
             blockData.sedes = form.find('#msh_avail_sedes').val() || [];
             blockData.programas = form.find('#msh_avail_programas').val() || [];
             blockData.rangos = form.find('#msh_avail_rangos').val() || [];

             // *** NUEVO: Recoger valores de checkboxes marcados ***
             blockData.sedes = form.find('input[name="msh_avail_sedes[]"]:checked').map(function() {
                return $(this).val(); // Devuelve el ID (value)
            }).get(); // Convierte a array estándar

            blockData.programas = form.find('input[name="msh_avail_programas[]"]:checked').map(function() {
                return $(this).val();
            }).get();

            blockData.rangos = form.find('input[name="msh_avail_rangos[]"]:checked').map(function() {
                return $(this).val();
            }).get();
            // *** FIN NUEVO ***


            // --- Validación Client-Side (Básica) ---
             var errors = [];
             if (!blockData.dia) errors.push('El campo Día es obligatorio.');
             if (!blockData.hora_inicio) errors.push('El campo Hora Inicio es obligatorio.');
             if (!blockData.hora_fin) errors.push('El campo Hora Fin es obligatorio.');
             if (blockData.hora_inicio && blockData.hora_fin && blockData.hora_fin <= blockData.hora_inicio) {
                 errors.push(msh_admin_data.text_validation_end_after_start || 'Hora fin debe ser posterior a inicio.');
             }
             var isDuplicate = false;
             for (var i = 0; i < currentAvailabilityData.length; i++) {
                 if (blockIndex !== -1 && i === blockIndex) continue;
                 if (currentAvailabilityData[i].dia === blockData.dia && currentAvailabilityData[i].hora_inicio === blockData.hora_inicio) {
                     isDuplicate = true; break;
                 }
             }
             if (isDuplicate) { errors.push(msh_admin_data.text_validation_duplicate_slot || 'Horario duplicado.'); }

             if (errors.length > 0) {
                 validationDiv.html(errors.join('<br>')).show();
                 return;
             }

             if (blockIndex === -1) { currentAvailabilityData.push(blockData); }
             else { currentAvailabilityData[blockIndex] = blockData; }

             availabilityChangesMade = true;
             renderAvailabilityTable();
             closeAvailabilityModal();
         });
    }

    // --- Eliminar Bloque de Disponibilidad (Actualiza array JS) ---
    availabilityManagerContainer.on('click', '.msh-delete-availability', function(e) {
        e.preventDefault();
        var index = $(this).data('index');
        if (typeof index === 'undefined' || !currentAvailabilityData[index]) { console.error('Índice inválido'); return; }
        var confirmMsg = msh_admin_data.text_confirm_delete_availability || '¿Eliminar este bloque?';
        if (confirm(confirmMsg)) {
            currentAvailabilityData.splice(index, 1);
            availabilityChangesMade = true;
            renderAvailabilityTable();
        }
     });

    // --- Guardar TODOS los Cambios de Disponibilidad (AJAX) ---
    availabilityManagerContainer.on('click', '#msh-save-availability-changes-btn', function(e) {
        e.preventDefault();
        var button = $(this);
        if (!availabilityChangesMade || button.prop('disabled')) return;
        var nonce = $('#msh_save_disponibilidad_nonce').val();
        if (!nonce) { availabilitySaveStatus.text('Error: Nonce Faltante.').css('color', 'red'); return; }

        button.prop('disabled', true);
        availabilitySpinner.addClass('is-active');
        availabilitySaveStatus.text(msh_admin_data.text_saving_availability || 'Guardando...').css('color', '');

        $.ajax({
            url: msh_admin_data.ajax_url, type: 'POST',
            data: {
                action: 'msh_save_disponibilidad',
                maestro_id: msh_admin_data.post_id,
                availability_data: JSON.stringify(currentAvailabilityData),
                security: nonce
            },
            success: function(response) {
                if (response.success) {
                    availabilityChangesMade = false;
                    availabilitySaveStatus.text(response.data.message || 'Guardado.').css('color', 'green');
                    updateSaveButtonState();
                } else {
                    availabilitySaveStatus.html(msh_admin_data.text_availability_save_error + '<br>' + (response.data.message || 'Error.')).css('color', 'red');
                    button.prop('disabled', false);
                }
            },
            error: function() {
                 availabilitySaveStatus.text(msh_admin_data.modal_error_saving || 'Error de conexión.').css('color', 'red');
                 button.prop('disabled', false);
            },
            complete: function() {
                availabilitySpinner.removeClass('is-active');
                setTimeout(function(){ availabilitySaveStatus.text(''); }, 5000);
            }
        });
    });

    // --- Inicialización Disponibilidad ---
    renderAvailabilityTable();


    // ============================================================
    // =          GESTIÓN DE CLASES PROGRAMADAS (MODAL)           =
    // ============================================================

    // --- Helpers para Modal de Clases ---
    function openClaseModal() {
         var width = $(window).width() * 0.8; var height = $(window).height() * 0.8;
         if (width > 800) width = 800; if (height > 650) height = 650;
         clasesModalContainer.hide(); // Ocultar primero
         clasesModalContent.html('<p>' + (msh_admin_data.modal_loading_form || 'Cargando...') + '</p>');
         tb_show(
             msh_admin_data.modal_title_manage_clase || 'Gestionar Clase',
             '#TB_inline?width=' + width + '&height=' + height + '&inlineId=msh-clase-modal-container', null
         );
         resetClaseModalMessages();
    }

    function closeClaseModal() {
        clasesModalContent.empty();
        clasesModalContainer.hide();
        tb_remove();
        clasesMaestroAvailabilityData = null; // Limpiar datos de disponibilidad para este modal
    }

    function resetClaseModalMessages() {
         var form = $('#msh-clase-form');
         if (form.length) {
             form.find('#msh-clase-validation-messages').html('').hide();
             form.find('#msh-clase-proximity-warning').html('').hide();
             form.find('.spinner').removeClass('is-active');
             form.find('#msh-save-clase-btn').prop('disabled', false);
         } else {
             clasesModalContent.find('#msh-clase-validation-messages').remove();
             clasesModalContent.find('#msh-clase-proximity-warning').remove();
         }
    }

    // --- Abrir Modal Añadir Clase ---
     maestroClasesContainer.on('click', '#msh-add-new-clase-btn', function(e){
        e.preventDefault();
        openClaseModal();
        var nonce = msh_admin_data.manage_clases_nonce;
        if (!nonce) { clasesModalContent.html('<p style="color:red;">Error: Nonce Faltante.</p>'); return; }
        $.ajax({
            url: msh_admin_data.ajax_url, type: 'POST',
            data: { action: 'msh_load_clase_form', maestro_id: $(this).data('maestro-id'), clase_id: 0, security: nonce },
            success: function(response) {
                if (response.success) {
                    clasesModalContent.html(response.data.html);
                    clasesMaestroAvailabilityData = response.data.maestro_availability;
                    attachClaseModalListeners(); // Adjuntar listeners
                    initializeDynamicFiltering(); // Inicializar filtros
                } else { clasesModalContent.html('<p style="color:red;">' + (response.data.message || 'Error.') + '</p>'); }
            },
            error: function() { clasesModalContent.html('<p style="color:red;">' + (msh_admin_data.modal_error_loading || 'Error.') + '</p>'); }
        });
     });

     // --- Abrir Modal Editar Clase ---
     maestroClasesContainer.on('click', '.msh-edit-clase', function(e){
         e.preventDefault();
         var claseId = $(this).data('clase-id');
         openClaseModal();
         var nonce = msh_admin_data.manage_clases_nonce;
         if (!nonce) { clasesModalContent.html('<p style="color:red;">Error: Nonce Faltante.</p>'); return; }
         $.ajax({
             url: msh_admin_data.ajax_url, type: 'POST',
             data: { action: 'msh_load_clase_form', maestro_id: $('#msh-add-new-clase-btn').data('maestro-id'), clase_id: claseId, security: nonce },
             success: function(response) {
                 if (response.success) {
                     clasesModalContent.html(response.data.html);
                     clasesMaestroAvailabilityData = response.data.maestro_availability;
                     attachClaseModalListeners(); // Adjuntar listeners
                     initializeDynamicFiltering(); // Inicializar filtros
                     // Forzar chequeo inicial al editar
                      var form = $('#msh-clase-form');
                      if (form.length) { form.find('#msh_clase_dia, #msh_clase_hora_inicio, #msh_clase_hora_fin').trigger('change'); }
                 } else { clasesModalContent.html('<p style="color:red;">' + (response.data.message || 'Error.') + '</p>'); }
             },
             error: function() { clasesModalContent.html('<p style="color:red;">' + (msh_admin_data.modal_error_loading || 'Error.') + '</p>'); }
         });
     });

     // --- Función para Adjuntar Listeners al Modal de Clases ---
     function attachClaseModalListeners() {
         var modalForm = $('#msh-clase-form');
         if (!modalForm.length) return;

         // Botón Cancelar
         modalForm.find('.msh-cancel-clase-btn').off('click').on('click', function(e) {
             e.preventDefault();
             closeClaseModal();
         });

         // Submit del Formulario (AJAX save_clase)
         modalForm.off('submit').on('submit', function(e) {
             e.preventDefault();
             var form = $(this);
             var submitButton = form.find('#msh-save-clase-btn');
             var spinner = form.find('.spinner');
             var validationMsgDiv = form.find('#msh-clase-validation-messages');
             var proximityWarningDiv = form.find('#msh-clase-proximity-warning');

             validationMsgDiv.html('').hide(); proximityWarningDiv.html('').hide();
             submitButton.prop('disabled', true); spinner.addClass('is-active');

             var startTime = form.find('#msh_clase_hora_inicio').val();
             var endTime = form.find('#msh_clase_hora_fin').val();
             if (startTime && endTime && endTime <= startTime) {
                 validationMsgDiv.html(msh_admin_data.validation_end_after_start || 'Hora fin debe ser posterior a inicio.').show();
                 submitButton.prop('disabled', false); spinner.removeClass('is-active'); return;
             }
             var saveNonce = form.find('#msh_save_clase_nonce').val();
             if (!saveNonce) {
                 validationMsgDiv.html('Error: Nonce Faltante.').show();
                 submitButton.prop('disabled', false); spinner.removeClass('is-active'); return;
             }

             $.ajax({
                 url: msh_admin_data.ajax_url, type: 'POST',
                 data: form.serialize() + '&action=msh_save_clase' + '&security=' + saveNonce,
                 success: function(response) {
                     if (response.success) {
                         closeClaseModal();
                         var newRowHtml = response.data.new_row_html; var claseId = response.data.new_clase_id; var isUpdate = response.data.is_update;
                         $('#msh-no-clases-row').remove();
                         if (isUpdate) { $('#msh-clase-row-' + claseId).replaceWith(newRowHtml); }
                         else { clasesTableBody.append(newRowHtml); }
                         var alertMessage = response.data.message || 'Guardado.';
                         if (response.data.warning) { var cleanWarning = $('<div>').html(response.data.warning).text(); alertMessage += '\n\nADVERTENCIA:\n' + cleanWarning; }
                         alert(alertMessage);
                     } else {
                         validationMsgDiv.html(response.data.message || 'Error.').show();
                         submitButton.prop('disabled', false);
                     }
                 },
                 error: function() { validationMsgDiv.html(msh_admin_data.modal_error_saving || 'Error.').show(); submitButton.prop('disabled', false); },
                 complete: function() { spinner.removeClass('is-active'); }
             });
         });
     }

    // --- Eliminar Clase (AJAX) ---
    maestroClasesContainer.on('click', '.msh-delete-clase', function(e) {
        e.preventDefault();
        var confirmMsg = msh_admin_data.confirm_delete_clase || '¿Eliminar clase?';
        if (!confirm(confirmMsg)) return;
        var button = $(this); var claseId = button.data('clase-id');
        var nonce = msh_admin_data.manage_clases_nonce; var row = $('#msh-clase-row-' + claseId);
        if (!nonce) { alert('Error: Nonce Faltante.'); return; }
        button.prop('disabled', true);
        $.ajax({
            url: msh_admin_data.ajax_url, type: 'POST',
            data: { action: 'msh_delete_clase', clase_id: claseId, security: nonce },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(300, function() {
                        $(this).remove();
                        if (clasesTableBody.find('tr').length === 0) {
                            var noClasesMsg = msh_admin_data.no_clases_msg || 'No hay clases.';
                            clasesTableBody.append('<tr id="msh-no-clases-row"><td colspan="7">' + noClasesMsg + '</td></tr>');
                        }
                    });
                    alert(response.data.message || 'Eliminado.');
                } else { alert('Error: ' + (response.data.message || 'No se pudo eliminar.')); button.prop('disabled', false); }
            },
            error: function() { alert(msh_admin_data.modal_error_deleting || 'Error.'); button.prop('disabled', false); }
        });
    });

    // --- Filtrado Dinámico Dropdowns Clases ---
    function initializeDynamicFiltering() {
        var form = $('#msh-clase-form'); if (!form.length) return;
        form.on('change', '#msh_clase_dia, #msh_clase_hora_inicio, #msh_clase_hora_fin', checkClassAvailabilityAndUpdateDropdowns);
    }

    function checkClassAvailabilityAndUpdateDropdowns() {
        var form = $(this).closest('form'); if (!form.length) form = $('#msh-clase-form'); if (!form.length) return;
        var diaSelect = form.find('#msh_clase_dia'); var horaInicioInput = form.find('#msh_clase_hora_inicio');
        var horaFinInput = form.find('#msh_clase_hora_fin'); var sedeSelect = form.find('#msh_clase_sede_id');
        var programaSelect = form.find('#msh_clase_programa_id'); var rangoSelect = form.find('#msh_clase_rango_id');
        var hints = form.find('.msh-availability-hint');
        var selectedDia = diaSelect.val(); var selectedHoraInicio = horaInicioInput.val(); var selectedHoraFin = horaFinInput.val();
        var hintText = msh_admin_data.availability_hint_text || 'No disponible/admisible.';

        hints.hide();
        sedeSelect.find('option').data('admisible', false); programaSelect.find('option').data('admisible', false); rangoSelect.find('option').data('admisible', false);

        if (!selectedDia || !selectedHoraInicio || !selectedHoraFin || !clasesMaestroAvailabilityData || selectedHoraFin <= selectedHoraInicio) {
            sedeSelect.find('option').prop('disabled', false).show(); programaSelect.find('option').prop('disabled', false).show(); rangoSelect.find('option').prop('disabled', false).show();
            return;
        }

        var timeStart = selectedHoraInicio; var timeEnd = selectedHoraFin; var foundBlock = null;
        var admissibleSedes = []; var admissibleProgramas = []; var admissibleRangos = [];

        if (Array.isArray(clasesMaestroAvailabilityData)) {
            for (var i = 0; i < clasesMaestroAvailabilityData.length; i++) {
                var block = clasesMaestroAvailabilityData[i];
                if (block && block.dia === selectedDia && block.hora_inicio && block.hora_fin && timeStart >= block.hora_inicio && timeEnd <= block.hora_fin) {
                    foundBlock = block;
                    admissibleSedes = Array.isArray(block.sedes) ? block.sedes : [];
                    admissibleProgramas = Array.isArray(block.programas) ? block.programas : [];
                    admissibleRangos = Array.isArray(block.rangos) ? block.rangos : [];
                    break;
                }
            }
        }

        updateClassSelectOptions(sedeSelect, admissibleSedes, foundBlock, hintText);
        updateClassSelectOptions(programaSelect, admissibleProgramas, foundBlock, hintText);
        updateClassSelectOptions(rangoSelect, admissibleRangos, foundBlock, hintText);
    }

    function updateClassSelectOptions(selectElement, admissibleIds, foundBlock, hintText) {
        if (!selectElement.length) return;
        var currentSelection = selectElement.val();
        selectElement.find('option:not(:first-child)').prop('disabled', false).show();

        selectElement.find('option:not(:first-child)').each(function() {
            var option = $(this); var optionId = parseInt(option.val(), 10);
            var isAdmissible = admissibleIds.map(Number).includes(optionId);
            option.data('admisible', isAdmissible);
            if (!isAdmissible) { option.prop('disabled', true).hide(); }
        });

        var selectedOption = selectElement.find('option[value="' + currentSelection + '"]');
        if (currentSelection !== "" && selectedOption.prop('disabled')) { selectElement.val(""); }

        var hint = selectElement.closest('td').find('.msh-availability-hint');
        if (!foundBlock) {
            selectElement.find('option:not(:first-child)').prop('disabled', true).hide(); selectElement.val(""); hint.html(hintText).show();
        } else if (selectElement.val() === "" && currentSelection !== "") { hint.html(hintText).show(); }
        else { hint.hide(); }
    }


}); // Fin del jQuery(document).ready()