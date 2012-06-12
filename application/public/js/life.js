var RAND_LIFE_DENSITY = 0.6;

var STATUS_LIVING     = 1;
var STATUS_STABILIZED = 10;
var STATUS_DEAD       = -1;

var CHSTATUS_ADD      = 1;
var CHSTATUS_KILL     = -1;
var CHSTATUS_NONE     = 0;

var lifeId, iteration, rows, cols, changes = {};


function isPosNonZeroInt(value) {
    return (value - 0) == value &&
            value > 0;
}

//--------------------------------------- GUI functions ---------------------------------------

function drawEmptyGrid(rows, cols, isEditable) {
    // Create the grid container.
    var lifeBitmap = _createLBitmap(rows, cols);

    for (var y = 0; y < rows; y++) {
        for (var x = 0; x < cols; x++) {
            _createGridElement(y, x, isEditable, lifeBitmap);
        }
    }
}

function drawChanges(cells) {
    var action, cell;

    for (var cellId in cells) {
        cell    = $('#' + cellId);
        action  = cells[cellId];

        if (action == CHSTATUS_ADD) {
            cell.addClass('living');
        } else if (action == CHSTATUS_KILL) {
            cell.removeClass('living');
        }
    }
}

function redrawBitmap(bitmap, isEditable) {
    var lifeBitmap = $('#lBitmap');

    // Remove the life bitmap if already exists.
    if (lifeBitmap.length) {
        lifeBitmap.remove();
    }

    // Evaluate rows and columns.
    var rows    = bitmap.length;
    var cols    = (bitmap[0]).length;

    // Create the grid container.
    lifeBitmap  = _createLBitmap(rows, cols);

    var element;

    for (var y = 0; y < rows; y++) {
        for (var x = 0; x < cols; x++) {
            element = _createGridElement(y, x, isEditable, lifeBitmap);

            if (bitmap[y][x] == 1) {
                element.addClass('living');
            }
        }
    }
}

function _createLBitmap(rows, cols) {
    var parent = $('#fLife');

    return $('<div />')
            .attr('id',     'lBitmap')
            .css('width',   cols * 15 + 'px')
            .css('height',  rows * 15 + 'px')
            .appendTo(parent);
}

function _createGridElement(y, x, isEditable, lifeBitmap) {
    var element = $('<div />')
                    .attr('id', y + '_' + x)
                    .appendTo(lifeBitmap);

    if (isEditable) {
        element.click(function() {
            toggleCell($(this));
        })
    }

    return element;
}

function toggleCell(cell) {
    var cellId      = cell.attr('id');
    var isLiving    = cell.hasClass('living');

    if (isLiving) {
        // Remove.
        changes[cellId] = CHSTATUS_KILL;
    } else {
        // Add.
        changes[cellId] = CHSTATUS_ADD;
    }

    cell.toggleClass('living');
}

function colourTweaked() {
    $('.living').css('background-color', '#FF0000');
}

function onEnd() {
    // Disable cells.
    $('#lBitmap').children('div').each(function() {
        $(this).off('click');
    });

    // Update the "Genaration #" headline.
    $('.itNum').text(iteration + 1);

    // Hide control buttons.
    $('#play').css('display', 'none');
    $('#pause').css('display', 'none');
    $('#next').css('display', 'none');
}

//--------------------------------------- Ajax function ---------------------------------------

function tick() {
    $.ajax({
        type    : 'post',
        url     : 'ajax/next',
        data    : 'id=' + lifeId,
        cache   : false,
        complete: function(data) {
            if (!data.responseText) {
                alert('An error has occurred!');
                return;
            }

            // Parse response.
            var response        = JSON.parse(data.responseText);
            var status          = response['status'];
            var newGenChanges   = response['changes'];

            // Apply changes to the grid.
            drawChanges(newGenChanges);

            // Check status.
            if (status == STATUS_STABILIZED) {
                // The end.
                onEnd();

                alert('The population has stabilized. The end of simulation.');
                return;
            }
            if (status == STATUS_DEAD) {
                // The end.
                onEnd();

                alert('The population has died out. The end of simulation.');
                return;
            }

            // Continue simulation.
            iteration           = response['iteration'];

            // Update the "Genaration #" headline.
            $('.itNum').text(iteration);
        }
    });
}

function saveTweaks(onCompleteCallback) {
    $.ajax({
        type    : 'post',
        url     : 'ajax/tweak',
        data    : 'id=' + lifeId + '&tweaks=' + JSON.stringify(changes),
        cache   : false,
        complete: function(data) {
            // Clear the "changes" variable.
            changes = {};

            if (onCompleteCallback) {
                onCompleteCallback();
            }
        }
    });
}

function loadPast(reqIteration) {
    $.ajax({
        type    : 'post',
        url     : 'ajax/past',
        data    : 'id=' + lifeId + '&it=' + reqIteration,
        cache   : false,
        complete: function(data) {
            if (!data.responseText) {
                alert('Invalid iteration requested!');
                return;
            }

            // Parse response.
            var response = JSON.parse(data.responseText);

            // OK.
            var tweaked = response['tweaked'];
            var bitmap  = response['bitmap'];

            // Draw the Life.
            redrawBitmap(bitmap, false);

            if (tweaked) {
                // If the loaded generation is a tweaked one, change the color of living cells.
                colourTweaked();
            }

            // Set current iteration.
            iteration = reqIteration;

            // Update the genaration # in GUI.
            $('.itNum').text(iteration);
            $('#iter').val(iteration);
        }
    });
}
