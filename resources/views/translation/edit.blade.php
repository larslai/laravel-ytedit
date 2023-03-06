<?php header("Access-Control-Allow-Origin: *"); ?>
@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
          <div class="col-lg-12 col-sm-12">
            <!-- 1. The <iframe> (and video player) will replace this <div> tag. -->
            <div id="player" style="width: 100%; height:320px;"></div>
          </div>
          <div class="col-lg-12 col-sm-12">
              <button class="btn btn-secondary" id="preious_btn">前へ</button>
              <button class="btn btn-secondary" id="play_btn">プレイ・ヘビロテ</button>
              <button class="btn btn-secondary" id="next_btn">次へ</button>
          </div>
          <div class="flex-container col-lg-12 col-sm-12" style="margin-top: 20px;">
            <div class="edit-container col-lg-12 col-sm-12">
              <div>
                <span id="showing_jp_subtitle">日本語字幕内容</span>
              </div>
              <div>
                <textarea id="edit_jp_area" class="edit-area" style="min-width: 100%; height: 100px;" placeholder="字幕編集ところ"></textarea>
              </div>
            </div>
            <div class="edit-container col-lg-12 col-sm-12" style="margin-top: 20px;">
              <div>
                <span id="showing_tw_subtitle">中国語字幕内容</span>
              </div>
              <div>
                <textarea id="edit_tw_area" class="edit-area" style="min-width: 100%; height: 100px;" placeholder="字幕編集ところ"></textarea>
              </div>
            </div>
          </div>
          <div class="col-lg-12 col-sm-12" style="margin-top: 20px;">
            <button class="btn btn-primary" id="update_btn">字幕更新</button>
          </div>
        </div>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"
          integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
          crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-csv/1.0.21/jquery.csv.min.js"></script>
        <script>
          var videoId = null;
          // 2. This code loads the IFrame Player API code asynchronously.
          var tag = document.createElement('script');

          tag.src = "https://www.youtube.com/iframe_api";
          var firstScriptTag = document.getElementsByTagName('script')[0];
          firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

          // 3. This function creates an <iframe> (and YouTube player)
          //    after the API code downloads.
          var player;
          var subtitleObject = {};
          function onYouTubeIframeAPIReady() {
            let searchParams = new URLSearchParams(window.location.search)
            if(searchParams.has('id')) {
              videoId = searchParams.get('id')
            }

            player = new YT.Player('player', {
              height: '390',
              width: '640',
              videoId: videoId,
              playerVars: {
                'playsinline': 1,
                'controls': 0
              },
              events: {
                'onReady': onPlayerReady,
                'onStateChange': onPlayerStateChange
              }
            });
          }

          // 4. The API will call this function when the video player is ready.
          function onPlayerReady(event) {
            //event.target.playVideo();
          }

          // 5. The API calls this function when the player's state changes.
          //    The function indicates that when playing a video (state=1),
          //    the player should play for six seconds and then stop.
          var done = false;
          //var playing = false;
          var intervalID;
          var showingSubtitle;
          var currentIndex = 0;
          var maxIndex = 0;
          function onPlayerStateChange(event) {
            /*
            console.log(event.data);
            if (event.data == YT.PlayerState.PAUSED) {
              playing = false;
            }
            */
            if (event.data == YT.PlayerState.PLAYING) {
              //playing = true;
              var subtitleObject = getSubtitle(currentIndex);
              var start = subtitleObject['start'];
              var end = subtitleObject['end'];
              //console.log("start at:", start);
              //console.log("end at:", end);
              //var duration = ((end - start) * 1000) - 400;
              var duration = ((end - start) * 1000);
              if(duration <= 0) {
                duration = 50;
              }

              showSubtitle(subtitleObject['jp'], subtitleObject['tw']);
              //console.log("duration:", duration);
              //console.log(subtitleObject['jp']);
              setTimeout(pauseVideo, duration);

              if(!done) {
                setTimeout(stopVideo, 6000);
                done = true;
              }
            }
          }
          function stopVideo() {
            player.stopVideo();
          }

          function playVideo() {
            var subtitleObject = getSubtitle(currentIndex);
            var start = subtitleObject['start'];
            if(start !== 0){
              //start = start - 0.06;
            }

            player.seekTo(start,true);
            player.playVideo();
          }

          function pauseVideo() {
            player.pauseVideo();
          }

          function showSubtitle(jp_txt, tw_txt) {
            $( "#showing_jp_subtitle" ).text(jp_txt);
            $( "#edit_jp_area" ).val(jp_txt);
            $( "#showing_tw_subtitle" ).text(tw_txt);
            $( "#edit_tw_area" ).val(tw_txt);
          }

          window.onload = function(e){
              loadJson();
              //intervalID = setInterval(getCurrentVideoTime, 100);
          }

          // 抓取YT影片播放時間
          /*
          function getCurrentVideoTime()
          {
            var currentTime = player.playerInfo.currentTime;
            //getSubtitle(currentTime);
          }
          */
          function loadJson(csvFile)
          {
            $.ajax({
              type : 'GET',
              dataType : 'json',
              async: false,
              url: '/storage/'+videoId+'.json',
              success : function(jsonObjects) {
                  subtitleObject = jsonObjects;
                  maxIndex = Object.keys(subtitleObject).length;
                  console.log("JSON load success");
                  console.log("maxIndex:", maxIndex);
                }
            });
          }

          function getSubtitle(index) {
            return subtitleObject[index];
          }
          /*
          function getSubtitle(time) {
            if(playing) {
              $.each(subtitleObject, function(index, data){
                if(time >= data['start'] && time <= data['end']) {
                  if(data['jp'] != showingSubtitle) {
                    showingSubtitle = data['jp'];
                    console.log(showingSubtitle);
                  }
                }
              })
            }
          }
          */
          function lockMode(type) {
            if(type) {
              $('textarea[id="edit_tw_area"]').prop('disabled', true);
              $('textarea[id="edit_jp_area"]').prop('disabled', true);
              $("#update_btn").attr('disabled', true);
            } else {
              $('textarea[id="edit_tw_area"]').prop('disabled', false);
              $('textarea[id="edit_jp_area"]').prop('disabled', false);
              $("#update_btn").attr('disabled', false);
            }
          }

          $( "#play_btn" ).click(function() {
            playVideo();
          });
          $( "#preious_btn" ).click(function() {
            if(currentIndex <= 0) {
              currentIndex = 0;
            } else {
              currentIndex = currentIndex - 1;
            }
            playVideo();
          });
          $( "#next_btn" ).click(function() {
            if(currentIndex >= maxIndex) {
              currentIndex = maxIndex;
            } else {
              currentIndex = currentIndex + 1;
            }
            playVideo();
          });
          $( "#update_btn" ).click(function() {
            lockMode(true);
            var twTxt =  $( "#edit_tw_area" ).val();
            var jpTxt =  $( "#edit_jp_area" ).val();
            console.log(videoId);
            console.log(currentIndex);
            console.log(jpTxt);
            console.log(twTxt);
            $.ajax({
              type : 'POST',
              url: "{{url('api/video')}}",
              headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
              },
              data: {
                "vid": videoId,
                "index": currentIndex,
                "jp": jpTxt,
                "tw": twTxt
              },
              dataType: "json",
              success : function(jsonObjects) {
                alert("更新成功.");
                lockMode(false);
              },
              error: function(XMLHttpRequest, textStatus, errorThrown) {
                alert("更新失敗");
                console.log(errorThrown);
              }
            });
          });

        </script>
    </div>
@endsection
