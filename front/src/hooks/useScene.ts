import { useState, useEffect } from "react";

interface Scene {
  id: string;
  title: string;
  content_markdown: string;
}

export function useScene(sceneId: string): {
  loading: boolean;
  scene: Scene | null;
  error?: string | null;
  nextTransitions?: string[];
  prevTransitions?: string[];
} {
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [scene, setScene] = useState<Scene | null>(null);
  const [nextTransitions, setNextTransitions] = useState<string[]>([]);
  const [prevTransitions, setPrevTransitions] = useState<string[]>([]);

  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        const [sceneRes, nextRes, prevRes] = await Promise.all([
          fetch(`http://localhost:8080/scenes/${sceneId}`),
          fetch(`http://localhost:8080/scenes/${sceneId}/transitions/next`),
          fetch(`http://localhost:8080/scenes/${sceneId}/transitions/prev`),
        ]);
        const sceneJson = await sceneRes.json();
        setScene(sceneJson.data);
        const nextJson = await nextRes.json();
        setNextTransitions(nextJson);
        const prevJson = await prevRes.json();
        setPrevTransitions(prevJson);
        setLoading(false);
      } catch {
        setError("erreur au chargemenbt");
        setLoading(false);
      }
    };
    fetchData();
  }, [sceneId]);

  return { loading, scene, error, nextTransitions, prevTransitions };
}
