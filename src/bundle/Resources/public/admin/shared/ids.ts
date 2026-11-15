/**
 * Typed access to the Ibexa Design System components (@ids-components).
 *
 * The design system ships JavaScript without typings, and its CommonJS re-exports are not visible to
 * TypeScript. The components used here are declared once, in this file, so the rest of the widget stays typed.
 */

import type { ComponentType, ReactNode } from 'react';
import * as ids from '@ids-components';

export type ButtonType = 'primary' | 'secondary' | 'tertiary' | 'secondary-alt' | 'tertiary-alt';

export interface ButtonProps {
    type?: ButtonType;
    size?: 'medium' | 'small';
    icon?: string;
    ariaLabel?: string;
    title?: string;
    disabled?: boolean;
    onClick?: () => void;
    extraAria?: Record<string, string | boolean>;
    children?: ReactNode;
}

export interface AssetsValue {
    getIconPath: (name: string) => string;
}

interface IdsComponents {
    Button: ComponentType<ButtonProps>;
    AssetsProvider: ComponentType<{ value: AssetsValue; children?: ReactNode }>;
}

const components = ids as unknown as IdsComponents;

export const Button = components.Button;
export const AssetsProvider = components.AssetsProvider;
