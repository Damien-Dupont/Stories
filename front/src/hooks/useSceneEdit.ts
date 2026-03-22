import { useState, useEffect } from "react";

type SaveStatus = "idle" | "success" | "error";

export function useSceneEdit(sceneId: string) {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [title, setTitle] = useState("");
  const [contentMarkdown, setContentMarkdown] = useState("");
  const [saveStatus, setSaveStatus] = useState<SaveStatus>("idle");

  const save = async () => {
    try {
      await fetch(`http://localhost:8080/scenes/${sceneId}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ title, content_markdown: contentMarkdown }),
      });
    } catch {
      setSaveStatus("error");
      setError("Erreur lors de la sauvegarde");
    }
  };

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

  return {
    title,
    contentMarkdown,
    loading,
    error,
    setTitle,
    setContentMarkdown,
    save,
    saveStatus,
  };
}
