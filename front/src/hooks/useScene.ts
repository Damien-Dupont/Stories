import { useState, useEffect } from "react";

interface Scene {
  id: string;
  title: string;
  content_markdown: string;
}

export function useScene(sceneId: string): {
  loading: boolean;
  scene: Scene | null;
} {
  const [loading, setLoading] = useState(true);
  const [scene, setScene] = useState<Scene | null>(null);
  useEffect(() => {
    const fetchData = async () => {
      setLoading(true);
      const res = await fetch(`http://localhost:8080/scenes/${sceneId}`);
      const json = await res.json();
      setScene(json.data);
      setLoading(false);
    };
    fetchData();
  }, [sceneId]);

  return { loading, scene };
}
