interface TransitionFormProps {
  scenes: { id: string; title: string }[];
  label: string;
  onTransitionCreate?: (sceneId: string) => void;
}

export function TransitionForm({
  scenes,
  label,
  onTransitionCreate,
}: TransitionFormProps) {
  return (
    <div>
      <input placeholder={label} />
      <select onChange={(e) => onTransitionCreate?.(e.target.value)}>
        {scenes.map((s) => (
          <option key={s.id} value={s.id}>
            {s.title}
          </option>
        ))}
      </select>
    </div>
  );
}
