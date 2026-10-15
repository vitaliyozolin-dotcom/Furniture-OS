export function BottomNavigation() {
  return (
    <nav className="bottom-nav" aria-label="Основная навигация">
      <button><span>⌂</span><small>Проекты</small></button>
      <button className="create"><span>＋</span><small>Новый шкаф</small></button>
      <button><span>◯</span><small>Кабинет</small></button>
    </nav>
  );
}
