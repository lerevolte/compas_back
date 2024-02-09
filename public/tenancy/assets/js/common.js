
// Dealing with Textarea Height
function calcHeightTextArea() {
  $("textarea").each(function () {
    console.log(this.clientHeight)
    this.setAttribute("style", "height:" + (this.clientHeight) + "px;");
  }).on("input", function () {
    this.style.height = "auto";
    this.style.height = (this.scrollHeight) + "px";
  });

}
