const nameInput = document.getElementById('name');
const base_priceInput = document.getElementById('base_price');
const base_distInput = document.getElementById('base_dist');
const dist_costInput = document.getElementById('dist_cost');
const csvFileInput = document.getElementById('csv-file');

var nameValidator = null;
var base_priceValidator = null;
var base_distValidator = null;
var dist_costValidator = null;
var csvFileValidator  = null;

document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("form").addEventListener("submit", (event) => onSubmit(event));
    
    nameValidator = new FormValidator(
        nameInput,
        (event) => {
            let val = event.target.value;
            let m = val.match(/[а-яa-z]{0,20}$/i)
            if (!m) {
                event.target.value = "";
            } else {
                event.target.value = m[0];
            }
        },
        (value) => (/[а-яa-z]{3,20}$/i.test(value)),
    )
    base_priceValidator = new FormValidator(
        base_priceInput,
        BasicValidators.posFloatPassiveValidator,
        BasicValidators.posFloatActiveValidator
    )
    base_distValidator = new FormValidator(
        base_distInput,
        BasicValidators.posFloatPassiveValidator,
        BasicValidators.posFloatActiveValidator
    )
    dist_costValidator = new FormValidator(
        dist_costInput,
        BasicValidators.posFloatPassiveValidator,
        BasicValidators.posFloatActiveValidator
    )
    csvFileValidator = new CSVValidator(csvFileInput)
})

function onSubmit(event) {
    let isValid = true;
    // Проверяем весь пак валидаторов
    [
        nameValidator,
        base_priceValidator,
        base_distValidator,
        dist_costValidator,
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
