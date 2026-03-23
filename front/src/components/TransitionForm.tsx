interface TransitionFormProps {
  scenes: { id: string; title: string }[];
}

export function TransitionForm({ scenes }: TransitionFormProps) {
  return (
    <div>
      <input placeholder={"Label de la transition"} />
      <select>
        {scenes.map((s) => (
          <option key={s.id}>{s.title}</option>
        ))}
      </select>
    </div>
  );
}
