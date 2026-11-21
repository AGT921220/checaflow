import type { Session } from "./Session.ts";

export interface SessionStore {
    current(): Promise<Session | null>;

    save(session: Session): Promise<void>;

    clear(): Promise<void>;
}
