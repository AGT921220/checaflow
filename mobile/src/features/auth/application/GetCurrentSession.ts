import type { Session } from "../domain/Session.ts";
import type { SessionStore } from "../domain/SessionStore.ts";

export class GetCurrentSession {
    constructor(private readonly sessions: SessionStore) {}

    async handle(): Promise<Session | null> {
        return this.sessions.current();
    }
}
