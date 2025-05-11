document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("form").addEventListener("submit", (event));
    const intershipInput = document.getElementById('intership');
    const car_licenseInput = document.getElementById('car_license');
    const car_brandInput = document.getElementById('car_brand');
    const tariff_idInput = document.getElementById('tariff_id');
    const csvFileInput = document.getElementById('csv-file');
    
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
    const csvFileValidator = new CSVValidator(csvFileInput)
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

    if (csvFileInput.files.length > 0){
        isValid = isValid || CSVValidator.validate();
    }

    if (!isValid) {
        event.preventDefault();
    };
}
