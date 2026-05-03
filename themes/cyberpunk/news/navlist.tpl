<div class="cp-nav-list">
{loop:news}
  <a class="cp-nav-item" href="{news:news_url}" title="{news:news_headline}">
    <span class="cp-nav-date">{news:news_time}</span>
    <span class="cp-nav-title">{news:news_short}</span>
  </a>
{stop:news}
</div>
