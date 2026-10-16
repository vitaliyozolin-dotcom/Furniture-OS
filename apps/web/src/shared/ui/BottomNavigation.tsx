export function BottomNavigation() {
  return (
    <nav className="bottom-nav" aria-label="Основная навигация">
      <a href="/projects"><span>⌂</span><small>Проекты</small></a>
      <a className="create" href="/constructor"><span>＋</span><small>Новый шкаф</small></a>
      <a href="/account"><span>◯</span><small>Кабинет</small></a>
    </nav>
  );
}
