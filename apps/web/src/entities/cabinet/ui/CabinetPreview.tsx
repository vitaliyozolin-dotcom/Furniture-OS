interface CabinetPreviewProps {
  open: boolean;
}

export function CabinetPreview({ open }: CabinetPreviewProps) {
  return (
    <div className={`cabinet-stage ${open ? 'is-open' : ''}`}>
      <div className="cabinet-shadow" />
      <div className="cabinet-shell">
        <div className="cabinet-inside">
          <div className="bay bay-left"><i/><i/><i/><i/></div>
          <div className="bay bay-center"><span className="rail"/><b/><b/><b/></div>
          <div className="bay bay-right"><i/><i/><i/><i/></div>
        </div>
        <div className="door door-left"><span/></div>
        <div className="door door-center"><span/></div>
        <div className="door door-right"><span/></div>
      </div>
    </div>
  );
}
