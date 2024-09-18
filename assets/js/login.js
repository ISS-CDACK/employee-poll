document.getElementById('toggle-password').addEventListener('click', function () {
    const passwordField = document.getElementById('password-field');
    const icon = this.querySelector('i');

    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    } else {
        passwordField.type = 'password';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    }
});

document.getElementById('password-field').addEventListener('input', function () {
    const toggleButton = document.getElementById('toggle-password');

    if (this.value.length > 0) {
        toggleButton.style.display = 'block';
    } else {
        toggleButton.style.display = 'none';
    }
});

document.addEventListener('DOMContentLoaded', function (e) {
    const form = document.getElementById('login-form');
    const fv = FormValidation.formValidation(
        form,
        {
            fields: {
                email: {
                    validators: {
                        notEmpty: {
                            message: "Please enter your CDAC email address"
                        },
                        regexp: {
                            regexp: /^[^\s@]+@cdac\.in$/i,
                            message: "Please enter a valid CDAC email address"
                        }
                    }
                },
                password: {
                    validators: {
                        notEmpty: {
                            message: "Please enter your password"
                        }
                    }
                },
                captcha: {
                    validators: {
                        notEmpty: {
                            message: "Please enter the captcha"
                        },
                        stringLength: {
                            min: 6,
                            max: 6,
                            message: "Please enter valid captcha"
                        }
                    },
                }
            },
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    eleValidClass: "",
                    rowSelector: ".mb-4"
                }),
                submitButton: new FormValidation.plugins.SubmitButton(),
                defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
                autoFocus: new FormValidation.plugins.AutoFocus(),
            },
            init: (instance) => {
                instance.on('plugins.message.placed', function (event) {
                    const parent = event.element.parentElement;
                    if (parent.classList.contains('input-group')) {
                        parent.insertAdjacentElement('afterend', event.messageElement);
                    }
                });

                instance.on('core.form.valid', function () {
                    showSpinner();
                    // console.log('Form is valid and will be submitted.');
                    // Additional actions can be performed here if needed
                });
            },
        }
    );

});