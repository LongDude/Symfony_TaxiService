document.addEventListener("DOMContentLoaded", () => {
    document.querySelector("form").addEventListener("submit", (event) => onSubmit(event));
    const nameInput = document.getElementsByClassName('inp_name')[0];
    const phoneInput = document.getElementsByClassName('inp_phone')[0];
    const emailInput = document.getElementsByClassName('inp_email')[0];

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