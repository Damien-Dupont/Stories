import { useState, useEffect } from "react";

interface Scene {
  id: string;
  title: string;
  content_markdown: string;
}

interface Transition {
  transition_id: string;
  transition_label: string;
  transition_order: number;
  scene_id: string;
  scene_title: string;
  emoji?: string | null;
  scene_type?: string;
}

export function useScene(sceneId: string): {
  loading: boolean;
  scene: Scene | null;
  error?: string | null;
  nextTransitions?: Transition[];
  prevTransitions?: Transition[];
} {
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [scene, setScene] = useState<Scene | null>(null);
  const [nextTransitions, setNextTransitions] = useState<Transition[]>([]);
  const [prevTransitions, setPrevTransitions] = useState<Transition[]>([]);

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
        setNextTransitions(nextJson.data);
        const prevJson = await prevRes.json();
        setPrevTransitions(prevJson.data);
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
