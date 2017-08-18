/* FOR EMPTY FIELDS */
function error(el)
{
    $(el).css("background-color", "red").css("color","yellow").val("Should not be blank");

    setTimeout(function()
    {
        $(el).css("background-color", "white").css("color","black");
        $(el).val("");
    }, 2000);            
}

/* FOR SELECT ELEMENT */
function sortOption(el)
{
    var options = $('#'+ el + ' option:not(:first)');
    var arr = options.map(function(_, o) { return { t: $(o).text(), v: o.value }; }).get();
    arr.sort(function(o1, o2) { return o1.t > o2.t ? 1 : o1.t < o2.t ? -1 : 0; });
    options.each(function(i, o) {
      o.value = arr[i].v;
      $(o).text(arr[i].t);
    });
}

/* FOR SELECT ELEMENT */
function addSuffix(element)
{
    $("#"+element).children("option:not(:first)").each(function()
    {
        var prefix = $(this).val();
        var html   = $(this).html();
        if(prefix.indexOf("id") == 0)
        {
            //console.log(prefix + "-" + html + " iOS");
            $(this).html(html + " - iOS");
        }
        else
        {
            ///console.log(prefix + "-" + html + " Android");
            $(this).html(html + " - Android");
        }
    });   
}