import type { AuthenticatedUser } from "./AuthenticatedUser.ts";

export interface CredentialsAuthenticator {
    authenticate(email: string, password: string): Promise<AuthenticatedUser | null>;
}
