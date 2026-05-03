<div class="cp-nav-list">
{loop:threads}
  <a class="cp-nav-item" href="{url:board_thread:where={threads:threads_id}:start={threads:new_posts}}" title="{threads:threads_headline}">
    <span class="cp-nav-date">{threads:threads_date}</span>
    <span class="cp-nav-title">{threads:threads_headline_short}</span>
  </a>
{stop:threads}
</div>
