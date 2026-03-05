$(document).ready(function() {
    // --- NUEVO: Gestión de Cursos ---
    $('.btn-manage-courses').click(function() {
        var participantId = $(this).data('id');
        var participantName = $(this).data('name');
        
        $('#manage-participant-id').val(participantId);
        $('#modal-participant-name').text(participantName);
        $('#enrolled-courses-list').html('<tr><td colspan="4" class="text-center">Cargando...</td></tr>');
        $('#available-courses-list').html('');
        $('#search-available-course').val('');
        
        $('#modal-manage-courses').modal('show');
        
        loadEnrolledCourses(participantId);
    });

    function loadEnrolledCourses(participantId) {
        $.ajax({
            url: 'index.php',
            method: 'GET',
            data: { page: 'participants', action: 'get_participant_courses', participant_id: participantId },
            dataType: 'json',
            success: function(response) {
                var html = '';
                if (response.error) {
                    html = '<tr><td colspan="4" class="text-danger">' + response.error + '</td></tr>';
                } else if (response.length === 0) {
                    html = '<tr><td colspan="4" class="text-center">No hay cursos matriculados.</td></tr>';
                } else {
                    response.forEach(function(course) {
                        html += '<tr>';
                        html += '<td>' + course.course_name + '</td>';
                        html += '<td>' + course.event_code + '</td>';
                        html += '<td>' + course.status + '</td>';
                        html += '<td>' + course.created_at + '</td>';
                        html += '<td>';
                        if (course.certificate_id) {
                            html += '<form method="post" action="index.php?page=certificates" class="d-inline" target="_blank">';
                            html += '<input type="hidden" name="certificate_id" value="' + course.certificate_id + '">';
                            html += '<input type="hidden" name="action" value="download_individual_pdf">';
                            html += '<button type="submit" class="btn btn-xs btn-primary" title="Descargar Certificado">';
                            html += '<i class="fas fa-file-download"></i>';
                            html += '</button>';
                            html += '</form>';
                        } else {
                            html += '<span class="text-muted" title="Sin certificado"><i class="fas fa-minus-circle"></i></span>';
                        }
                        html += '</td>';
                        html += '</tr>';
                    });
                }
                $('#enrolled-courses-list').html(html);
            },
            error: function() {
                $('#enrolled-courses-list').html('<tr><td colspan="4" class="text-danger">Error al cargar cursos.</td></tr>');
            }
        });
    }

    var searchTimeout;
    $('#search-available-course').on('input', function() {
        clearTimeout(searchTimeout);
        var query = $(this).val();
        var participantId = $('#manage-participant-id').val();
        
        searchTimeout = setTimeout(function() {
            if (query.length >= 3) {
                searchAvailableCourses(participantId, query);
            } else {
                 $('#available-courses-list').html('');
            }
        }, 500);
    });
    
    $('#btn-search-course').click(function() {
        var query = $('#search-available-course').val();
        var participantId = $('#manage-participant-id').val();
        searchAvailableCourses(participantId, query);
    });

    function searchAvailableCourses(participantId, query) {
        $('#available-courses-list').html('<div class="text-center p-2">Buscando...</div>');
        
        $.ajax({
            url: 'index.php',
            method: 'GET',
            data: { page: 'participants', action: 'search_available_courses', participant_id: participantId, q: query },
            dataType: 'json',
            success: function(response) {
                var html = '';
                if (response.error) {
                    html = '<div class="text-danger p-2">' + response.error + '</div>';
                } else if (response.length === 0) {
                    html = '<div class="text-muted p-2">No se encontraron cursos disponibles.</div>';
                } else {
                    response.forEach(function(course) {
                        html += '<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center btn-enroll-course" data-id="' + course.id + '">';
                        html += '<div><strong>' + course.event_code + '</strong> - ' + course.name + ' <span class="badge badge-info">' + (course.type_name || '') + '</span></div>';
                        html += '<span class="badge badge-primary badge-pill"><i class="fas fa-plus"></i> Matricular</span>';
                        html += '</button>';
                    });
                }
                $('#available-courses-list').html(html);
            },
            error: function() {
                $('#available-courses-list').html('<div class="text-danger p-2">Error al buscar cursos.</div>');
            }
        });
    }

    $(document).on('click', '.btn-enroll-course', function() {
        var courseId = $(this).data('id');
        var participantId = $('#manage-participant-id').val();
        var btn = $(this);
        var badge = btn.find('.badge-pill');
        var originalBadgeHtml = badge.html();
        
        // Usar SweetAlert2 para confirmación
        Swal.fire({
            title: '¿Estás seguro?',
            text: "¿Deseas matricular al participante en este curso?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, matricular',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true);
                badge.html('<i class="fas fa-spinner fa-spin"></i> Matriculando...');
                
                $.ajax({
                    url: 'index.php?page=participants&action=enroll_participant',
                    method: 'POST',
                    data: { participant_id: participantId, course_id: courseId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: '¡Matriculado!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            loadEnrolledCourses(participantId);
                            // Recargar lista de disponibles para actualizar estado (o quitar el curso matriculado)
                            searchAvailableCourses(participantId, $('#search-available-course').val());
                        } else {
                            Swal.fire(
                                'Error',
                                response.error || 'Desconocido',
                                'error'
                            );
                            btn.prop('disabled', false);
                            badge.html('<i class="fas fa-redo"></i> Reintentar');
                        }
                    },
                    error: function() {
                        Swal.fire(
                            'Error',
                            'Error de conexión al matricular.',
                            'error'
                        );
                        btn.prop('disabled', false);
                        badge.html('<i class="fas fa-redo"></i> Reintentar');
                    }
                });
            }
        });
    });

    // Abrir modal y cargar datos
    $('.btn-edit-participant').on('click', function() {
        var participantId = $(this).data('id');
        var enrollmentId = $(this).data('enrollment-id');
        
        // Limpiar formulario
        $('#formEditParticipant')[0].reset();
        
        // Cargar datos via AJAX
        $.ajax({
            url: 'index.php',
            type: 'GET',
            data: {
                page: 'participants',
                action: 'get_participant',
                id: participantId,
                enrollment_id: enrollmentId
            },
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    Swal.fire('Error', response.error, 'error');
                } else {
                    $('#edit_participant_id').val(response.id);
                    $('#edit_enrollment_id').val(response.enrollment_id);
                    $('#edit_first_name').val(response.first_name);
                    $('#edit_last_name').val(response.last_name);
                    $('#edit_email').val(response.email);
                    $('#edit_identity_document').val(response.identity_document);
                    $('#edit_phone').val(response.phone);
                    $('#edit_notes').val(response.notes);
                    $('#edit_enrollment_status').val(response.enrollment_status);
                    
                    $('#modalEditParticipant').modal('show');
                }
            },
            error: function() {
                Swal.fire('Error', 'Error al cargar los datos del participante.', 'error');
            }
        });
    });

    // Guardar cambios
    $('#formEditParticipant').on('submit', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: '¿Guardar cambios?',
            text: "¿Estás seguro de actualizar los datos del participante?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'index.php?page=participants&action=update_participant',
                    type: 'POST',
                    data: $('#formEditParticipant').serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: '¡Actualizado!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                $('#modalEditParticipant').modal('hide');
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.error || 'Error al actualizar.', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Error al procesar la solicitud.', 'error');
                    }
                });
            }
        });
    });
});