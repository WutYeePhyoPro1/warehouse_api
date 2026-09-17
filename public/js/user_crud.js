$(document).ready(function() {
    $(document).on('click', '#openModal', function() {
        const form = $('#formData');
        form[0].reset();
        $('#formId').val('');
        $('#formMethod').val('POST');
        form.find('.error-text').empty();
        form.find('#p_required, #pc_required').show();
        $('#userModal').show();
    });

    $(document).on('click', '.editModal', function() {
        let id = $(this).data('id');
        const form = $('#formData');
        $.ajax({
            type: 'GET',
            url: `/admin/users/${id}/edit`,
            dataType: "json",
            success: function(res) {
                let user = res.user;

                form.find('#p_required, #pc_required').hide();

                form.find('#name').val(user.name);
                form.find('#emp_id').val(user.emp_id);
                form.find('#password').val('');
                form.find('#password_confirmation').val('');
                form.find('select[name="title"]').val(user.title).change();
                form.find('select[name="branch_id[]"]').val(res.branch_ids).change();
                form.find('select[name="role_id"]').val(res.role_id).change();
                form.find('select[name="status"]').val(String(user.status)).change();
                form.find('.error-text').empty();
                $('#formId').val(id);
                $('#formMethod').val('PUT');
                $('#userModal').show();
            },
            error: function(error) {
                console.log(error);
            }
        });
    });

    $('#formData').on('submit', function(e) {
        e.preventDefault();
        let form = $(this);
        let formData = new FormData(form[0]);
    
        let method = $('#formMethod').val();
        let id = $('#formId').val();
        let actionUrl = (method === 'POST') ? '/admin/users' : `/admin/users/${id}`;
    
        if (method === 'PUT') {
            formData.append('_method', 'PUT');
        }
    
        $.ajax({
            url: actionUrl,
            type: 'POST', 
            data: formData,
            processData: false, 
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') 
            },
            success: function(response) {
                console.log(response);
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.success 
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                let response = xhr.responseJSON;
                if (xhr.status === 422 && response.errors) {
                    $('.error-text').empty();
                    $.each(response.errors, function(field, messages) {
                        $('#' + field + 'Error').text(messages[0]);
                    });
                } else {

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.error 
                    });
                }
            }
        });
    });

    $(document).on('click', '.deleteModal', function() {
        let id = $(this).data('id'); 
        let url = $(this).data('url'); 
        Swal.fire({
            icon: 'warning',
            title: 'Are you sure?',
            text: 'You won\'t be able to revert this!',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url, 
                    method: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'), 
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.success 
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.error
                        });
                    }
                });
            }
        });
        
    });

});


