const intershipInput = document.getElementsByClassName('inp_intership')[0];
const car_licenseInput = document.getElementsByClassName('inp_car_license')[0];
const car_brandInput = document.getElementsByClassName('inp_car_brand')[0];
const tariff_idInput = document.getElementsByClassName('inp_tariff_id')[0];
document.addEventListener("DOMContentLoaded", () => {
    document.querySelector("form").addEventListener("submit", (event));
    
    const intershipValidator = new FormValidator(
        intershipInput,
        BasicValidators.posNumberPassiveValidator,
        BasicValidators.posNumberActiveValidator
    )
    const car_licenseValidator = new FormValidator(
        car_licenseInput,
        (event) => {
          let val = event.target.value;
          let m = val.match(/[а-я0-9A-Z]{0,8}[ -]?[а-я0-9A-Z]{0,4}/i);
          if (!m) {
            event.target.value = "";
          } else {
            event.target.value = m[0];
          }
        },
        (value) => /[а-я0-9A-Z]{4,8}[ -][а-я0-9A-Z]{2,4}/i.test(value)
      )

    const car_brandValidator = new FormValidator(
        car_brandInput,
        (event) => {},
        (value) => value.length > 0 && value.length < 50
    )
    
    const tariff_idValidator = new FormValidator(
        tariff_idInput,
        (event) => {},
        (value) => value > 0 
    )
})

function onSubmit(event) {
    let isValid = true;
    // Проверяем весь пак валидаторов
    [
        intershipValidator,
        car_licenseValidator,
        car_brandValidator,
        tariff_idValidator,
    ].forEach(validator => {
        isValid &= validator.validate()
    })


    if (!isValid) {
        event.preventDefault();
    };
}
