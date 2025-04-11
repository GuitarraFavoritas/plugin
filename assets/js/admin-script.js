// assets/js/admin-script.js
jQuery(document).ready(function($) {

    // ============================================================
    // =      CÓDIGO: GESTIÓN DISPONIBILIDAD REPETIBLE           =
    // ============================================================
    // Renombramos las variables y funciones para evitar conflictos
    var containerDisponibilidad = $('#msh-bloques-disponibilidad');
    var templateDisponibilidadHtml = $('#msh-bloque-plantilla').html();
    var noBloquesMsgSelector = '#msh-no-bloques';

    // --- Función para actualizar índices y UI de Disponibilidad ---
    function msh_update_availability_rows() {
        var rows = containerDisponibilidad.find('.msh-bloque-row');
        rows.each(function(rowIndex) {
            $(this).find('select, input').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    // Regex más específica para el formato msh_disponibilidad[INDEX]...
                    var newName = name.replace(/msh_disponibilidad\[(\d+|{{INDEX}})\]/, 'msh_disponibilidad[' + rowIndex + ']');
                    $(this).attr('name', newName);

                    var id = $(this).attr('id');
                    if (id) {
                         // Regex para ID: msh_disponibilidad_INDEX_campo o msh_disponibilidad_NUMBER_campo
                        var oldIdPattern = /msh_disponibilidad_(\d+|{{INDEX}})_/;
                        if(oldIdPattern.test(id)){
                            var newId = id.replace(oldIdPattern, 'msh_disponibilidad_' + rowIndex + '_');
                            $(this).attr('id', newId);
                            // Actualizar label 'for' asociada
                           $(this).closest('.msh-bloque-field-group').find('label[for="' + id.replace(/\[/g, '\\[').replace(/\]/g, '\\]') + '"]').attr('for', newId); // Escapar corchetes si se usan en IDs
                           // O si la label siempre sigue el patrón de ID:
                           // $('label[for="' + id + '"]').attr('for', newId); // Menos robusto si el ID cambia mucho
                        }
                    }
                }
            });
        });

        // Mostrar/ocultar mensaje si no hay bloques (usando texto de msh_admin_data)
        var noBlocksMsgText = (typeof msh_admin_data !== 'undefined' && msh_admin_data.no_blocks_msg)
                               ? msh_admin_data.no_blocks_msg
                               : 'Aún no se han añadido bloques de disponibilidad.'; // Fallback

        if (rows.length === 0) {
            if ($(noBloquesMsgSelector).length === 0) {
                containerDisponibilidad.append('<p id="' + noBloquesMsgSelector.substring(1) + '">' + noBlocksMsgText + '</p>');
            }
            $(noBloquesMsgSelector).show();
        } else {
            $(noBloquesMsgSelector).remove();
        }
    }

    // --- Añadir Bloque de Disponibilidad ---
    $('#msh-add-bloque').on('click', function(e) {
        e.preventDefault();
        if (!templateDisponibilidadHtml) {
             console.error("MSH Error: Plantilla de bloque de disponibilidad no encontrada.");
             return;
        }
        var nextIndex = containerDisponibilidad.find('.msh-bloque-row').length;
        // Usar regex global 'g' para reemplazar todas las ocurrencias de {{INDEX}}
        var nuevoBloqueHtml = templateDisponibilidadHtml.replace(/\{\{INDEX\}\}/g, nextIndex);
        containerDisponibilidad.append(nuevoBloqueHtml);
        msh_update_availability_rows(); // Llamar a la función renombrada
    });

    // --- Eliminar Bloque de Disponibilidad (con delegación) ---
    containerDisponibilidad.on('click', '.msh-remove-bloque', function(e) {
        e.preventDefault();
        // Usar texto de confirmación de msh_admin_data
        var confirmMsg = (typeof msh_admin_data !== 'undefined' && msh_admin_data.confirm_delete_disponibilidad)
                         ? msh_admin_data.confirm_delete_disponibilidad
                         : '¿Estás seguro de que quieres eliminar este bloque de disponibilidad?'; // Fallback
        if (confirm(confirmMsg)) {
            $(this).closest('.msh-bloque-row').remove();
            msh_update_availability_rows(); // Llamar a la función renombrada
        }
    });

    // --- Inicializar UI de Disponibilidad al cargar ---
    msh_update_availability_rows();

    // --- Opcional: Sortable para Disponibilidad ---
    /*
    if ($.fn.sortable && containerDisponibilidad.length > 0) {
         containerDisponibilidad.sortable({
             handle: '.msh-drag-handle',
             items: '.msh-bloque-row',
             axis: 'y',
             placeholder: 'msh-sortable-placeholder',
             forcePlaceholderSize: true,
             update: function( event, ui ) {
                 msh_update_availability_rows();
             }
         }).disableSelection();
     }
    */


    // ============================================================
    // =      NUEVO CÓDIGO: GESTIÓN DE CLASES PROGRAMADAS         =
    // ============================================================

    // Asegurarse que msh_admin_data exista antes de usarlo
    if (typeof msh_admin_data === 'undefined') {
        console.error('MSH Error: msh_admin_data no está definido. Verifica wp_localize_script.');
        return; // Detener la ejecución si faltan datos esenciales
    }

    var maestroClasesContainer = $('.msh-clases-container');
    var modalContainer = $('#msh-clase-modal-container');
    var modalContent = $('#msh-clase-modal-content');
    var clasesTableBody = $('#msh-clases-list');
    var maestroAvailabilityData = null;

    // --- Helpers para Modal ---
    function openClaseModal() {
        var width = $(window).width() * 0.8;
        var height = $(window).height() * 0.8;
        if (width > 800) width = 800;
        if (height > 650) height = 650;

        // Usar texto del objeto localizado
        tb_show(
            msh_admin_data.modal_title_manage_clase || 'Gestionar Clase Programada',
            '#TB_inline?width=' + width + '&height=' + height + '&inlineId=msh-clase-modal-container',
            null
        );
        modalContainer.show();
        resetModalMessages();
    }

    function closeClaseModal() {
        tb_remove();
        // Usar texto del objeto localizado
        modalContent.html('<p>' + (msh_admin_data.modal_loading_form || 'Cargando formulario...') + '</p>');
        maestroAvailabilityData = null;
    }

    function resetModalMessages() {
        // Asegurarse que el formulario exista antes de buscar elementos dentro
        var form = $('#msh-clase-form');
        if (form.length) {
             form.find('#msh-clase-validation-messages').html('').hide();
             form.find('#msh-clase-proximity-warning').html('').hide();
             form.find('.spinner').removeClass('is-active');
             form.find('#msh-save-clase-btn').prop('disabled', false);
        } else {
             // Si el form no existe aún (al abrir modal), limpiar el contenedor principal
             modalContent.find('#msh-clase-validation-messages').remove();
             modalContent.find('#msh-clase-proximity-warning').remove();
        }
    }

    // --- Abrir Modal para Añadir Nueva Clase ---
    maestroClasesContainer.on('click', '#msh-add-new-clase-btn', function(e) {
        e.preventDefault();
        resetModalMessages();
        modalContent.html('<p>' + (msh_admin_data.modal_loading_form || 'Cargando formulario...') + '</p>');
        openClaseModal();

        var maestroId = $(this).data('maestro-id');
        // Usar nonce del objeto localizado
        var nonce = msh_admin_data.manage_clases_nonce;

        if (!nonce) {
            modalContent.html('<p style="color:red;">Error: Nonce de seguridad no encontrado.</p>');
            return;
        }

        $.ajax({
            // Usar URL ajax del objeto localizado
            url: msh_admin_data.ajax_url,
            type: 'POST',
            data: {
                action: 'msh_load_clase_form',
                maestro_id: maestroId,
                clase_id: 0,
                security: nonce // Nombre del campo nonce esperado por check_ajax_referer
            },
            success: function(response) {
                if (response.success) {
                    modalContent.html(response.data.html);
                    maestroAvailabilityData = response.data.maestro_availability;
                    initializeDynamicFiltering();
                } else {
                    modalContent.html('<p style="color:red;">' + (response.data.message || 'Error desconocido.') + '</p>');
                }
            },
            error: function() {
                // Usar texto del objeto localizado
                modalContent.html('<p style="color:red;">' + (msh_admin_data.modal_error_loading || 'Error de conexión al cargar el formulario.') + '</p>');
            }
        });
    });

    // --- Abrir Modal para Editar Clase ---
    maestroClasesContainer.on('click', '.msh-edit-clase', function(e) {
        e.preventDefault();
        resetModalMessages();
        modalContent.html('<p>' + (msh_admin_data.modal_loading_form || 'Cargando formulario...') + '</p>');
        openClaseModal();

        var claseId = $(this).data('clase-id');
        var maestroId = $('#msh-add-new-clase-btn').data('maestro-id');
        var nonce = msh_admin_data.manage_clases_nonce;

         if (!nonce) {
            modalContent.html('<p style="color:red;">Error: Nonce de seguridad no encontrado.</p>');
            return;
        }

        $.ajax({
            url: msh_admin_data.ajax_url,
            type: 'POST',
            data: {
                action: 'msh_load_clase_form',
                maestro_id: maestroId,
                clase_id: claseId,
                security: nonce
            },
            success: function(response) {
                if (response.success) {
                    modalContent.html(response.data.html);
                    maestroAvailabilityData = response.data.maestro_availability;
                    initializeDynamicFiltering();
                     // Forzar chequeo al cargar datos de edición
                     var form = $('#msh-clase-form');
                     if (form.length) {
                        form.find('#msh_clase_dia, #msh_clase_hora_inicio, #msh_clase_hora_fin').trigger('change');
                     }
                } else {
                    modalContent.html('<p style="color:red;">' + (response.data.message || 'Error desconocido.') + '</p>');
                }
            },
            error: function() {
                 modalContent.html('<p style="color:red;">' + (msh_admin_data.modal_error_loading || 'Error de conexión al cargar el formulario.') + '</p>');
            }
        });
    });

    // --- Cancelar/Cerrar Modal ---
    // Escucha en el documento para elementos dentro del modal cargado dinámicamente
    $(document).on('click', '#msh-clase-modal-container .msh-cancel-clase-btn', function(e) {
        e.preventDefault();
        closeClaseModal();
    });

    // --- Guardar Clase (Submit Modal) ---
    $(document).on('submit', '#msh-clase-form', function(e) {
        e.preventDefault();
        var form = $(this);
        // Mover reset aquí para que los mensajes no se borren si la validación JS falla
        // resetModalMessages(); // Se llama al inicio de la función

        var submitButton = form.find('#msh-save-clase-btn');
        var spinner = form.find('.spinner');
        var validationMsgDiv = form.find('#msh-clase-validation-messages');
        var proximityWarningDiv = form.find('#msh-clase-proximity-warning');

        // Limpiar mensajes previos específicos de este formulario
        validationMsgDiv.html('').hide();
        proximityWarningDiv.html('').hide();

        submitButton.prop('disabled', true);
        spinner.addClass('is-active');

        // Validación JS básica hora fin > hora inicio
        var startTime = form.find('#msh_clase_hora_inicio').val();
        var endTime = form.find('#msh_clase_hora_fin').val();
        if (startTime && endTime && endTime <= startTime) {
            // Usar texto de msh_admin_data
            var timeErrorMsg = msh_admin_data.validation_end_after_start || 'La hora de fin debe ser posterior a la hora de inicio.';
            validationMsgDiv.html(timeErrorMsg).show();
            submitButton.prop('disabled', false);
            spinner.removeClass('is-active');
            return;
        }

        // Obtener nonce específico para guardar
        var saveNonce = form.find('#msh_save_clase_nonce').val();
         if (!saveNonce) {
             validationMsgDiv.html('Error: Nonce de guardado no encontrado.').show();
            submitButton.prop('disabled', false);
            spinner.removeClass('is-active');
            return;
        }


        $.ajax({
            url: msh_admin_data.ajax_url,
            type: 'POST',
            // Serializar formulario y añadir action y nonce de guardado
            data: form.serialize() + '&action=msh_save_clase' + '&security=' + saveNonce,
            success: function(response) {
                if (response.success) {
                    closeClaseModal();

                    var newRowHtml = response.data.new_row_html;
                    var claseId = response.data.new_clase_id;
                    var isUpdate = response.data.is_update;

                    $('#msh-no-clases-row').remove();

                    if (isUpdate) {
                        $('#msh-clase-row-' + claseId).replaceWith(newRowHtml);
                    } else {
                        clasesTableBody.append(newRowHtml);
                    }

                    // Usar un sistema de notificaciones de WP sería mejor, pero alert por simplicidad
                    var alertMessage = response.data.message || 'Clase guardada.';
                    if (response.data.warning) {
                        // Limpiar HTML de la advertencia antes de mostrarla
                        var cleanWarning = $('<div>').html(response.data.warning).text();
                        alertMessage += '\n\nADVERTENCIA:\n' + cleanWarning;
                    }
                    alert(alertMessage);

                } else {
                    // Mostrar errores en el div de validación del modal
                    validationMsgDiv.html(response.data.message || 'Error desconocido al guardar.').show();
                    submitButton.prop('disabled', false); // Reactivar botón
                }
            },
            error: function() {
                // Usar texto de msh_admin_data
                validationMsgDiv.html(msh_admin_data.modal_error_saving || 'Error de conexión al guardar. Inténtalo de nuevo.').show();
                submitButton.prop('disabled', false);
            },
            complete: function() {
                // Asegurarse que el spinner siempre se quite
                spinner.removeClass('is-active');
                // El botón se reactiva solo en caso de error
            }
        });
    });

    // --- Eliminar Clase ---
    maestroClasesContainer.on('click', '.msh-delete-clase', function(e) {
        e.preventDefault();
        // Usar texto de confirmación de msh_admin_data
        var confirmDeleteMsg = msh_admin_data.confirm_delete_clase || '¿Estás seguro de que quieres eliminar esta clase permanentemente?';
        if (!confirm(confirmDeleteMsg)) {
            return;
        }

        var button = $(this);
        var claseId = button.data('clase-id');
        // Usar nonce general para acciones
        var nonce = msh_admin_data.manage_clases_nonce;
        var row = $('#msh-clase-row-' + claseId);

         if (!nonce) {
            alert('Error: Nonce de seguridad no encontrado.');
            return;
        }

        button.prop('disabled', true);

        $.ajax({
            url: msh_admin_data.ajax_url,
            type: 'POST',
            data: {
                action: 'msh_delete_clase',
                clase_id: claseId,
                security: nonce
            },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(300, function() {
                        $(this).remove();
                        if (clasesTableBody.find('tr').length === 0) {
                             // Usar texto de msh_admin_data
                             var noClasesMsg = msh_admin_data.no_clases_msg || 'Este maestro no tiene clases programadas.';
                            clasesTableBody.append('<tr id="msh-no-clases-row"><td colspan="7">' + noClasesMsg + '</td></tr>');
                        }
                    });
                    alert(response.data.message || 'Clase eliminada.');
                } else {
                    alert('Error: ' + (response.data.message || 'No se pudo eliminar la clase.'));
                    button.prop('disabled', false);
                }
            },
            error: function() {
                 // Usar texto de msh_admin_data
                alert(msh_admin_data.modal_error_deleting || 'Error de conexión al eliminar.');
                button.prop('disabled', false);
            }
        });
    });

    // --- Filtrado Dinámico de Dropdowns en el Modal ---
    // (Esta parte es compleja y depende de la estructura exacta de maestroAvailabilityData)
    function initializeDynamicFiltering() {
        var form = $('#msh-clase-form');
        if (!form.length) return; // Salir si el formulario no existe

        var diaSelect = form.find('#msh_clase_dia');
        var horaInicioInput = form.find('#msh_clase_hora_inicio');
        var horaFinInput = form.find('#msh_clase_hora_fin');

        // Añadir listeners DENTRO del contexto del formulario modal
        form.on('change', '#msh_clase_dia, #msh_clase_hora_inicio, #msh_clase_hora_fin', checkAvailabilityAndUpdateDropdowns);
    }

    function checkAvailabilityAndUpdateDropdowns() {
        var form = $(this).closest('form'); // Encontrar el formulario desde el elemento que cambió
        if (!form.length) form = $('#msh-clase-form'); // Fallback si no se encuentra el form padre
        if (!form.length) return; // Salir si aún no se encuentra

        var diaSelect = form.find('#msh_clase_dia');
        var horaInicioInput = form.find('#msh_clase_hora_inicio');
        var horaFinInput = form.find('#msh_clase_hora_fin');
        var sedeSelect = form.find('#msh_clase_sede_id');
        var programaSelect = form.find('#msh_clase_programa_id');
        var rangoSelect = form.find('#msh_clase_rango_id');
        var hints = form.find('.msh-availability-hint');

        var selectedDia = diaSelect.val();
        var selectedHoraInicio = horaInicioInput.val();
        var selectedHoraFin = horaFinInput.val();
        var hintText = msh_admin_data.availability_hint_text || 'No disponible o no admisible para este horario/día.';

        // Ocultar todos los hints y resetear estado 'admisible' inicial
        hints.hide();
        sedeSelect.find('option').data('admisible', false);
        programaSelect.find('option').data('admisible', false);
        rangoSelect.find('option').data('admisible', false);

        // Resetear opciones si falta información clave
        if (!selectedDia || !selectedHoraInicio || !selectedHoraFin || !maestroAvailabilityData) {
            sedeSelect.find('option').prop('disabled', false).show();
            programaSelect.find('option').prop('disabled', false).show();
            rangoSelect.find('option').prop('disabled', false).show();
            return;
        }

        // No filtrar si el rango horario es inválido
        if (selectedHoraFin <= selectedHoraInicio) {
             sedeSelect.find('option').prop('disabled', false).show();
             programaSelect.find('option').prop('disabled', false).show();
             rangoSelect.find('option').prop('disabled', false).show();
            return;
        }

        var timeStart = selectedHoraInicio;
        var timeEnd = selectedHoraFin;
        var foundBlock = null;
        var admissibleSedes = [];
        var admissibleProgramas = [];
        var admissibleRangos = [];

        // Iterar sobre la disponibilidad general del maestro
         if (Array.isArray(maestroAvailabilityData)) {
             for (var i = 0; i < maestroAvailabilityData.length; i++) {
                 var block = maestroAvailabilityData[i];
                 // Asegurarse que las propiedades existan
                 if (block && block.dia === selectedDia && block.hora_inicio && block.hora_fin &&
                     timeStart >= block.hora_inicio &&
                     timeEnd <= block.hora_fin)
                 {
                     foundBlock = block;
                     // Asegurarse que las propiedades de arrays existan
                     admissibleSedes = Array.isArray(block.sedes) ? block.sedes : [];
                     admissibleProgramas = Array.isArray(block.programas) ? block.programas : [];
                     admissibleRangos = Array.isArray(block.rangos) ? block.rangos : [];
                     break;
                 }
             }
         }


        // Función auxiliar para actualizar un select
        function updateSelectOptions(selectElement, admissibleIds) {
            if (!selectElement.length) return; // Salir si el select no existe

            var currentSelection = selectElement.val();
            var firstOption = selectElement.find('option:first'); // Guardar la opción "-- Seleccionar --"

            // Habilitar y mostrar todas las opciones temporalmente (excepto la primera)
            selectElement.find('option:not(:first-child)').prop('disabled', false).show();

            // Filtrar: deshabilitar y ocultar no admisibles
            selectElement.find('option:not(:first-child)').each(function() {
                var option = $(this);
                var optionId = parseInt(option.val(), 10);
                 // Convertir IDs admisibles a números para comparación segura
                 var isAdmissible = admissibleIds.map(Number).includes(optionId);

                option.data('admisible', isAdmissible); // Guardar estado
                if (!isAdmissible) {
                    option.prop('disabled', true).hide();
                }
            });

             // Re-evaluar la selección actual: Si la opción seleccionada ahora está oculta/deshabilitada, resetear
             var selectedOption = selectElement.find('option[value="' + currentSelection + '"]');
             if (currentSelection !== "" && selectedOption.prop('disabled')) {
                 selectElement.val(""); // Resetear a '-- Seleccionar --'
             }

             // Mostrar hint si no se encontró bloque o si la selección fue reseteada
             var hint = selectElement.closest('td').find('.msh-availability-hint');
             if (!foundBlock) {
                 selectElement.find('option:not(:first-child)').prop('disabled', true).hide(); // Deshabilitar todo si no hay bloque
                 selectElement.val("");
                 hint.html(hintText).show();
             } else if (selectElement.val() === "" && currentSelection !== "") {
                  // Si se reseteó la selección porque no era válida
                   hint.html(hintText).show();
             }
              else {
                 hint.hide();
             }
        }

        // Actualizar los selects
        updateSelectOptions(sedeSelect, admissibleSedes);
        updateSelectOptions(programaSelect, admissibleProgramas);
        updateSelectOptions(rangoSelect, admissibleRangos);

    } // Fin de checkAvailabilityAndUpdateDropdowns

}); // Fin del jQuery(document).ready()