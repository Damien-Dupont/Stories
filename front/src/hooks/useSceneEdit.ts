import { useState, useEffect } from "react";

export function useSceneEdit(sceneId: string) {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [title, setTitle] = useState("");
  const [contentMarkdown, setContentMarkdown] = useState("");

  useEffect(() => {
    const fetchData = async () => {
      try {
        const res = await fetch(`http://localhost:8080/scenes/${sceneId}`);
        const json = await res.json();
        setTitle(json.data.title);
        setContentMarkdown(json.data.content_markdown);
        setLoading(false);
      } catch {
        setError("Erreur au chargement");
        setLoading(false);
      }
    };
    fetchData();
  }, [sceneId]);

  return { title, contentMarkdown, loading, error };
}
