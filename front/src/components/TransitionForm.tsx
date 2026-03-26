interface TransitionFormProps {
  scenes: { id: string; title: string }[];
  label: string;
}

export function TransitionForm({ scenes, label }: TransitionFormProps) {
  return (
    <div>
      <input placeholder={label} />
      <select>
        {scenes.map((s) => (
          <option key={s.id}>{s.title}</option>
        ))}
      </select>
    </div>
  );
}
