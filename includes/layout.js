function show_confirm($url, $id, $action)
{
    var r=confirm("Are you sure?");
    if (r==true) {
        window.location.href = $url + '&id=' + $id + '&action=' + $action;
    } else {
        window.location.href = $url;
    }
}

