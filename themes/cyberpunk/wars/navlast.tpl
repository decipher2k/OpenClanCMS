<div class="cp-war-card">
  <a href="{url:squads_view:id={war:squads_id}}" title="{war:squads_name}">
    <img src="{page:path}uploads/squads/{war:squads_picture}" alt="{war:squads_name}" />
  </a>

  <div class="cp-war-score">
    <span>vs</span><br />
    <a href="{url:wars_view:id={war:wars_id}}">
      {if:draw}<span style="color: #bbb">{war:score1}</span> : <span style="color: #bbb">{war:score2}</span>{stop:draw}
      {unless:draw}
      {if:win}<span style="color: #00cc3a">{war:score1}</span> : <span style="color: #cc3333">{war:score2}</span>{stop:win}
      {unless:win}<span style="color: #cc3333">{war:score1}</span> : <span style="color: #00cc3a">{war:score2}</span>{stop:win}
      {stop:draw}
    </a>
  </div>

  <a href="{url:clans_view:id={war:clans_id}}" title="{war:clans_name}">
    <img src="{page:path}uploads/clans/{war:clans_picture}" alt="{war:clans_name}" />
  </a>
</div>
