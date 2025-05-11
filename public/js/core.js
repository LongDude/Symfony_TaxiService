
class FormValidator {
    constructor(inputElement, inputValidator, valueValidator) {
        this.element = inputElement;
        let nxt = this.element.nextElementSibling
        // Если следущий элемент - метка для текущего
        this.hasInfo = false;
        this.hiddenErr = false;
        if (nxt !== null) {
            if (this.element.getAttribute("name") == nxt.getAttribute("for")) {
                this.hasInfo = true;
                if (nxt.hidden) {
                    this.hiddenErr = true;
                }
            }
        }
        this.element.addEventListener("input", inputValidator);
        this.valueValidator = valueValidator;
    }

    validate() {
        let isValid = this.valueValidator(this.element.value);
        this.toggleError(!isValid);
        return isValid;
    }

    toggleError(bool) {
        if (bool) {
            this.element.classList.add("invalid");
            if (this.hasInfo){
                this.element.nextElementSibling.classList.add("invalid-info");
                if (this.hiddenErr) {
                    this.element.nextElementSibling.hidden = false;
                }
            }
        }
        else {
            this.element.classList.remove("invalid");
            if (this.hasInfo){
                this.element.nextElementSibling.classList.remove("invalid-info");
                if (this.hiddenErr) {
                    this.element.nextElementSibling.hidden = true;
                }
            }
        }
    }
}

class CSVValidator {
    constructor (fileInput){
        this.element = fileInput;
        let nxt = this.element.nextElementSibling
        this.hasInfo = false;
        this.hiddenErr = false;
        if (nxt !== null) {
            if (this.element.getAttribute("name") == nxt.getAttribute("for")) {
                this.hasInfo = true;
                this.nxt = nxt;
                if (nxt.hidden) {
                    this.hiddenErr = true;
                }
            }
        }
    }

    validate() {
        let err = '';
        if (!file.name.endsWith(".csv") || file.type !== "text/csv") {
            err = "WRONG FILE FORMAT\n";
        }
    
        if (file.size > 1 << 21) {
            err += "FILE TOO BIG.\n";
        }
        let isValid = (err == '');
        this.nxt.textContent = isValid ? "" : err;
        this.toggleError(isValid)
        return isValid
    }

    toggleError(bool) {
        if (bool) {
            this.element.classList.add("invalid");
            if (this.hasInfo){
                this.element.nextElementSibling.classList.add("invalid-info");
                if (this.hiddenErr) {
                    this.element.nextElementSibling.hidden = false;
                }
            }
        }
        else {
            this.element.classList.remove("invalid");
            if (this.hasInfo){
                this.element.nextElementSibling.classList.remove("invalid-info");
                if (this.hiddenErr) {
                    this.element.nextElementSibling.hidden = true;
                }
            }
        }
    }
}

class BasicValidators {
    static emailPassiveValidator(event) {
        let val = event.target.value
        let m = val.match(/^(?:[a-zA-Z]\w*)(?:\.[a-zA-Z0-9]\w*)*@?(?:(?<=@)[a-zA-Z]*)?(?:(?<=[a-zA-Z])\.?[a-zA-Z]*)?/)
        if (!m) {
            event.target.value = ''
        } else {
            event.target.value = m[0]
        }
    }
    static emailActiveValidator(value) {
        return (/^[a-zA-Z]\S*@[a-zA-Z]+\.[a-zA-Z]+$/.test(value))
    }

    static phonePassiveValidator(event) {
        let val = event.target.value.replaceAll(/\D+/g, "").substring(0, 11);
        let m = val.match(/(\d)(\d{1,3})?(\d{1,3})?(\d{1,2})?(\d{1,2})?/);
        if (!m) {
            event.target.value = "";
        } else {
            event.target.value =
                `+${m[1]}` +
                (m[2] ? ` (${m[2]}` : "") +
                (m[3] ? `) ${m[3]}` : "") +
                (m[4] ? `-${m[4]}` : "") +
                (m[5] ? `-${m[5]}` : "");
        }
    }
    static phoneActiveValidator(value) {
        return (value.replaceAll(/\D+/g, "").substring(0, 11).length == 11)
    }

    static posFloatPassiveValidator(event){
        let val = event.target.value;
        let m = val.match(/\d+\.?\d*/);
        if (!m){
            event.target.value = "";
        }
        else {
            event.target.value = m[0];
        }
    }
    static posFloatActiveValidator(value){
        return (/\d+(?:\.\d*)?$/.test(value));
    }

    static posNumberPassiveValidator(event){
        let val = event.target.value;
        let m = val.match(/\d+/);
        if (!m){
            event.target.value = "";
        }
        else {
            event.target.value = m[0];
        }
    }
    static posNumberActiveValidator(value){
        return (/\d+$/.test(value));
    }
}