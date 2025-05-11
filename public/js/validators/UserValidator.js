document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("form").addEventListener("submit", (event) => onSubmit(event));
    const nameInput = document.getElementById('name');
    const phoneInput = document.getElementById('phone');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const reppasswordInput = document.getElementById('repeat_password');

    const nameValidator = new FormValidator(
        nameInput,
        (event) => {
            let val = event.target.value;
            let m = val.match(/[a-zа-я ]{0,20}$/i)
            if (!m) {
                event.target.value = "";
            } else {
                event.target.value = m[0];
            }
        },
        (value) => (/\w{3,20}$/.test(value)),
    )

    const phoneValidator = new FormValidator(
        phoneInput,
        BasicValidators.phonePassiveValidator,
        BasicValidators.phoneActiveValidator,
    )
    
    const emailValidator = new FormValidator(
        emailInput,
        BasicValidators.emailPassiveValidator,
        BasicValidators.emailActiveValidator
    )

    const passwordValidator = new FormValidator(
        passwordInput,
        (event) => {
            let val = event.target.value;
            let m = val.match(/[a-z\d!@#$%^&*]{0,20}$/i)
            if (!m) {
                event.target.value = "";
            } else {
                event.target.value = m[0];
            }
        },
        (value) => (/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{8,}$/.test(value)),
    )

    const reppasswordValidator = new FormValidator(
        reppasswordInput,
        () => {},
        (value) => value == passwordInput.value
    )
})

function onSubmit(event) {
    let isValid = true;
    // Проверяем весь пак валидаторов
    [
        nameValidator,
        phoneValidator,
        emailValidator,
        passwordValidator,
        reppasswordValidator
    ].forEach(validator => {
        isValid &= validator.validate()
    })

    if (!isValid) {
        event.preventDefault();
    };
}