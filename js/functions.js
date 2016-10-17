/**
 * Shows or hides the custom packet div
 */
function toggleDiv()
{
    var div = document.getElementById('customPacket');
    var txt = document.getElementById('customPacketText');

    if(div.style.display == 'none') {
        div.style.display = 'block';
    } else {
        txt.value = '';
        div.style.display = 'none';
    }
    return false;
}

/**
 * Escapes meta-characters in JQuery selector.
 *
 * @param string selector
 */
function escapeSelector(selector)
{
    return selector.replace(/\[/g, '\\[').replace(/\]/g, '\\]');
}

/**
 * Does a multiply Ajax request
 *
 * @param string id parameter id
 * @param boolean remove if set to true will remove the parameter
 */
function multiplyParameter(id, remove)
{
    showLoad();
    action = remove ? 'remove' : 'multiply';
    query = 'index.php?' + action + '=' + escape(id);
    $('#inputData').load(query, $('#testFunction').serializeArray(),
        function() {
            hideLoad();
        }
    );
}

/**
 * Does a function form request.
 *
 * @param string f function name
 */
function chooseFunction(f)
{
    showLoad();

    functions = document.getElementById('functionsSelect');
    query = 'index.php?f=' + ((f != null) ? f : functions.options[functions.selectedIndex].value);

    // Ajax requests
    $('#inputData').load(query, null,
        function() {
            $('#links').load('index.php?l=1', null,
                function() {
                    document.getElementById('resultData').innerHTML = '';
                    hideLoad();
                    createUploadElements();
                }
            );
        } );
}

/**
 * Change server request
 */
function chooseServer()
{
    servers = document.getElementById('serverSelect');
    window.location = 'index.php?RELOAD_FUNCTIONS=1&server='+encodeURIComponent(servers.options[servers.selectedIndex].value);
}

/**
 * Change session request
 */
function chooseSession()
{
    showLoad();
    sessions = document.getElementById('sessionSelect');
    query = 'index.php?session=' + escape(sessions.options[sessions.selectedIndex].value);
    $.get(query, null,
        function() {
            document.getElementById('resultData').innerHTML = '';
            hideLoad();
        }
    );
}

/**
 * Change rpc protocol
 */
function chooseProtocol()
{
    showLoad();
    protocols = document.getElementById('protocolSelect');
    query = 'index.php?protocol=' + escape(protocols.options[protocols.selectedIndex].value);
    $.get(query, null,
        function() {
            document.getElementById('resultData').innerHTML = '';
            hideLoad();
        }
    );
}

/**
 * Validates the form and sends the request through Ajax request
 *
 * @return boolean true if the request was made, false otherwise
 */
function getResponse()
{
    clearErrors();

    // validate parameters if not sending custom packet
    if(document.getElementById('customPacket').style.display == 'block' || validateParams('params')) {
        showLoad();
        query = 'index.php';
        $('#resultData').load(query, $('#testFunction').serializeArray(),
            function() {
                query = 'index.php?sessionsForm=1'
                $('#sessionsFormContainer').load(query, null, 
                    function() {
                        hideLoad();
                    }
                );
            }
        );
        return true;
    } else {
        document.getElementById('resultData').innerHTML = '';
        return false;
    }
}

/**
 * Parses recursively the parameters and validates them.
 * It also reveals errors in case of invalid values.
 *
 * @param string param id
 * @return boolean true if the form is valid
 */
function validateParams(param)
{
    var valid = true;
    var i = 0;
    var integerRegexp = /^[+-]?\d+/;
    while((element = document.getElementById(param + '[' + i + '][value]')) != null) {
        nullElement = document.getElementById(param + '[' + i + '][null]');
        // if the element is not disabled
        if(element.className != 'disabled' && !nullElement.checked) {
            if(element.type == 'text') {
                // validate element
                // set error labels ids
                blankErrorElementId = element.id.replace('[value]', '[blank]');
                intErrorElementId   = element.id.replace('[value]', '[int]');
                // validate empty fields
                if((errorElement = document.getElementById(blankErrorElementId)) != null
                    && element.value == '') {
                    errorElement.style.display = 'block';
                    valid = false;
                // validate integer fields
                } else if((errorElement = document.getElementById(intErrorElementId)) != null
                          && element.value.match(integerRegexp) == null
                         ) {
                    errorElement.style.display = 'block';
                    valid = false;
                }
            }
            // call the parser recursively for enabled complex elements
            if(element.tagName == 'SPAN' && false == validateParams(param + '[' + i + '][members]')) {
                valid = false;
            }
        }

        i++;
    }
    return valid;
}

/**
 * Clears all visible errors
 */
function clearErrors()
{
    var collection = document.getElementById('testFunction').elements;
    for (i = 0; i < collection.length; i++) {
        element = collection.item(i);
        blankErrorElementId = element.id.replace('[value]', '[blank]');
        intErrorElementId   = element.id.replace('[value]', '[int]');

        if((errorElement = document.getElementById(blankErrorElementId)) != null
           && errorElement.style.display == 'block'
          ) {
                errorElement.style.display = 'none';
        }
        if((errorElement = document.getElementById(intErrorElementId)) != null
           && errorElement.style.display == 'block'
          ) {
                errorElement.style.display = 'none';
        }
    }

    return true;
}

/**
 * Displays the loading element
 */
function showLoad()
{
    $('#loader').get(0).style.display = 'block';
}

/**
 * Hides the loading element
 */
function hideLoad()
{
    $('#loader').get(0).style.display = 'none';
}

/**
 * Enable or disable an optional field
 *
 * @param id field id
 */
function enableField(id)
{
    var element = document.getElementById(id + "[value]");
    var nullElement = document.getElementById(id + "[null]");

    element.className = (element.className == 'disabled') ? 'enabled' : 'disabled';

    if(element.className == 'enabled') {
        nullElement.disabled = false;
    } else {
        nullElement.disabled = true;
        if(document.getElementById(id + "[blank]") != null) {
            document.getElementById(id + "[blank]").style.display = 'none';
        }
        if(document.getElementById(id + "[int]") != null) {
            document.getElementById(id + "[int]").style.display = 'none';
        }
    }

    return true;
}

/**
 * Null or un-null a field
 *
 * @param id field id
 */
function nullField(id)
{
    var element = document.getElementById(id + "[value]");
    var nullElement = document.getElementById(id + "[null]");

    // un-null field
    if(!nullElement.checked) {
        element.disabled = false;

        switch(element.tagName) {
            case 'SELECT':
                element.options[0].innerHTML = '';
                element.selectedIndex = element.lastValue;
                break;
            case 'INPUT':
                element.value = element.lastValue;
                break;
        }
    // null field
    } else {
        element.disabled = true;

        switch(element.tagName) {
            case 'SELECT':
                element.options[0].innerHTML = 'null';
                element.lastValue = element.selectedIndex;
                element.selectedIndex = 0;
                element.options[0].value = 'null';
                break;
            case 'INPUT':
                element.lastValue = element.value;
                element.value = 'null';
                break;
        }
    }

    return true;
}

/**
 * Creates Ajax uploader for file input elements.s
 */
function createUploadElements()
{
    var collection = document.getElementById('testFunction').elements;
    for (i = 0; i < collection.length; i++) {
        element = collection.item(i);
        // select every file parameter
        if(element.id.match(/\[file\]/)) {
            // create the selector
            var selector = '#' + element.parentNode.id;
            $.ajax_upload(escapeSelector(selector), {
                // Location of the serverside upload script
                action: 'index.php',
                // File upload name
                name: element.parentNode.id,
                // Additional data to send
                data: {},
                /**
                * Callback function that gets called when user selects file
                * @param filename File name of the file that was selected
                * @param extension Extension of that file
                * @return You can return false to cancel upload
                */
                onSubmit: function(filename, extension) {
                    showLoad();
                },
                /**
                * Callback function that gets called when file upload is completed
                * @param filename File name of the file that was selected
                * @param response Server script output
                */
                onComplete: function(filename, response) {
                    id = this.settings.name.replace('[value]', '[file]');
                    document.getElementById(id).value = filename;
                    hideLoad();
                },
                /**
                * Callback function that gets called when server returns "success" string
                * @param filename File name of the file that was selected
                */
                onSuccess: function(filename){},
                /**
                * Callback function that gets called when server returns something else,
                * not the "success" string
                * @param filename File name of the file that was selected
                * @param response Server script output
                */
                onError: function(filename, response) {}
                }
            );
        }
    }
}
