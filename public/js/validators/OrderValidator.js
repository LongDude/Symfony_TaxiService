document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("form").addEventListener("submit", (event));
    const phoneInput = document.getElementById('phone');
    const from_locInput = document.getElementById('from_loc');
    const dest_locInput = document.getElementById('dest_loc');
    const distanceInput = document.getElementById('distance');
    const tariff_idInput = document.getElementById('tariff_id');
    const driver_idInput = document.getElementById('driver_id');
    const csvFileInput = document.getElementById('csv-file');
    
    
    const phoneValidator = new FormValidator(
        phoneInput,
        BasicValidators.phonePassiveValidator,
        BasicValidators.phoneActiveValidator,
    )

    const from_locValidator = new FormValidator(
        from_locInput,
        (event) => {
            let val = event.target.value
            let m = val.match(/-?\d{0,3}\.?(?:(?<=\.)\d{0,6})?(?:(?<=\d{6});)?(?:(?<=;)-)?(?:(?<=[-;])\d{0,3})?(?:(?<=\d{1,3})\.)?(?:(?<=\.)\d{0,6})?/);
            if (!m){
                event.target.value = ''
            }
            else{
                event.target.value = m[0]
            }
        },
        (value) => (/-?\d{1,3}\.\d{6};-?\d{1,3}\.\d{6}/.test(value))
    )
    const dest_locValidator = new FormValidator(
        dest_locInput,
        (event) => {
            let val = event.target.value
            let m = val.match(/-?\d{0,3}\.?(?:(?<=\.)\d{0,6})?(?:(?<=\d{6});)?(?:(?<=;)-)?(?:(?<=[-;])\d{0,3})?(?:(?<=\d{1,3})\.)?(?:(?<=\.)\d{0,6})?/);
            if (!m){
                event.target.value = ''
            }
            else{
                event.target.value = m[0]
            }
        },
        (value) => (/-?\d{1,3}\.\d{6};-?\d{1,3}\.\d{6}/.test(value))
    )
    const distanceValidator = new FormValidator(
        distanceInput,
        BasicValidators.posFloatPassiveValidator,
        BasicValidators.posFloatActiveValidator
    )
    const tariff_idValidator = new FormValidator(
        tariff_idInput,
        (event) => {},
        (value) => value > 0 
    )
    const driver_idValidator = new FormValidator(
        driver_idInput,
        (event) => {},
        (value) => value > 0 
    )

    const csvFileValidator = new CSVValidator(csvFileInput)
})

function onSubmit(event) {
    let isValid = true;
    // Проверяем весь пак валидаторов
    [
        phoneValidator,
        from_locValidator,
        dest_locValidator,
        distanceValidator,
        tariff_idValidator,
        driver_idValidator,
    ].forEach(validator => {
        isValid &= validator.validate()
    })

    if (csvFileInput.files.length > 0){
        isValid = isValid || CSVValidator.validate();
    }

    if (!isValid) {
        event.preventDefault();
    };
}
