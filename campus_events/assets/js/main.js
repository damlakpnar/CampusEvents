function showFormError(form, message){
  let box = form.querySelector(".js-form-error");
  if(!box){
    box = document.createElement("div");
    box.className = "alert alert-error js-form-error";
    form.prepend(box);
  }
  box.textContent = message;
}

document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("form[data-validate='true']").forEach(form => {
    form.addEventListener("submit", (e) => {
      const old = form.querySelector(".js-form-error");
      if(old) old.remove();

      const requiredFields = form.querySelectorAll("[required]");
      for (const el of requiredFields){
        if(!(el.value || "").trim()){
          e.preventDefault();
          showFormError(form, "Lütfen tüm alanları doldurun.");
          el.focus();
          return;
        }
      }

      const email = form.querySelector("input[type='email']");
      if(email){
        const v = email.value.trim();
        if(!/^\S+@\S+\.\S+$/.test(v)){
          e.preventDefault();
          showFormError(form, "E-posta formatı geçersiz.");
          email.focus();
          return;
        }
      }
    });
  });
});
