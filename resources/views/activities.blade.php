<!DOCTYPE html>
<html>
<head>
    <title>Real-Time Laravel with Pusher</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <link rel="stylesheet" type="text/css" href="http://d3dhju7igb20wy.cloudfront.net/assets/0-4-0/all-the-things.css" />
    <style>
        .chat-app {
            width: 400px;
            margin: auto;
            margin-top: 50px;
        }

        #status_form {
            margin-bottom: 15px;
        }

        #status_text {
            width: 100%;
            padding-bottom: 10px;
            margin-top: 15px;
        }

        #activities {
            height: 450px;
            max-height: 450px;
            overflow: auto;
        }

        .chat-app .message:first-child {
            margin-top: 15px;
        }

        .like-heart {
            color: red;
            cursor: pointer;
        }

        .message-data {
            float: right;
            margin-top: 9px !important;
        }

        .activity-text {
            float: left;
            width: 275px;
            margin-top: 9px !important;
        }
    </style>

    <script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
    <script src="//js.pusher.com/3.0/pusher.min.js"></script>

    <script>
        // Ensure CSRF token is sent with AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Added Pusher logging
        Pusher.log = function(msg) {
            console.log(msg);
        };
    </script>
</head>
<body class="blue-gradient-background">

<div class="chat-app light-grey-blue-background">
    <form class="container" id="status_form" action="/activities/status-update" method="post">
        <div class="action-bar">
            <input id="status_text" name="status_text" class="input-message col-xs-9" placeholder="What's your status?" />
        </div>
    </form>

    <div class="time-divide">
        <span class="date">
          Today
        </span>
    </div>

    <div id="activities"></div>
</div>

<script id="activity_template" type="text/template">
    <div class="message activity">
        <div class="avatar">
            <img src="" />
        </div>
        <div class="text-display">
            <p class="message-body activity-text"></p>
            <div class="message-data">
                <span class="timestamp"></span>
                <span class="likes"><span class="like-heart">&hearts;</span><span class="like-count"></span></span>
            </div>
        </div>
    </div>
</script>

<script>
    function init() {
        // set up form submission handling
        $('#status_form').submit(statusUpdateSubmit);

        // monitor clicks on activity elements
        $('#activities').on('click', handleLikeClick);
    }

    // Handle the form submission
    function statusUpdateSubmit() {
        var statusText = $('#status_text').val();
        if(statusText.length < 3) {
            return;
        }

        // Build POST data and make AJAX request
        var data = {status_text: statusText};
        $.post('/activities/status-update', data).success(statusUpdateSuccess);

        // Ensure the normal browser event doesn't take place
        return false;
    }

    // Handle the success callback
    function statusUpdateSuccess() {
        $('#status_text').val('')
        console.log('status update submitted');
    }

    // Creates an activity element from the template
    function createActivityEl() {
        var text = $('#activity_template').text();
        var el = $(text);
        return el;
    }

    // Handles the like (heart) element being clicked
    function handleLikeClick(e) {
        var el = $(e.srcElement || e.target);
        if (el.hasClass('like-heart')) {
            var activityEl = el.parents('.activity');
            var activityId = activityEl.attr('data-activity-id');
            sendLike(activityId);
        }
    }

    // Makes a POST request to the server to indicate an activity being `liked`
    function sendLike(id) {
        $.post('/activities/like/' + id).success(likeSuccess);
    }

    // Success callback handler for the like POST
    function likeSuccess() {
        console.log('like posted');
    }

    // Increments the like count for an activity
    function incrementLikeCount(id) {
        var activityEl = $('#activities .activity[data-activity-id=' + id + ']');
        var likeCountEl = activityEl.find('.like-count');
        var currentCount = parseInt(likeCountEl.text() || 0, 10);
        likeCountEl.text(++currentCount);
    }

    function addActivity(type, data) {
        var activityEl = createActivityEl();
        activityEl.addClass(type + '-activity');
        activityEl.find('.activity-text').text(data.text);
        activityEl.attr('data-activity-id', data.id);
        activityEl.find('.avatar img').attr('src', 'https://robohash.org/' + data.id);

        $('#activities').prepend(activityEl);
    }

    // Handle the status update liked event
    function addLike(data) {
        addActivity('like', data);

        incrementLikeCount(data.likedActivityId);
    }

    // Handle the user visited the activities page event
    function addUserVisit(data) {
        addActivity('user-visit', data);
    }

    // Handle the status update event
    function addStatusUpdate(data) {
        addActivity('status-update', data);
    }

    $(init);

    /***********************************************/

    var pusher = new Pusher('{{env("PUSHER_KEY")}}');

    // TODO: Subscribe to the channel
    var channel = pusher.subscribe('activities');

    // TODO: bind to each event on the channel
    // and assign the appropriate handler
    channel.bind('new-status-update', addStatusUpdate);
    channel.bind('user-visit', addUserVisit);
    channel.bind('status-update-liked', addLike);

</script>

</body>
</html>