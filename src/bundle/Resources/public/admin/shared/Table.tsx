import React from 'react';
import type { ReactNode } from 'react';

export interface Column<T> {
    key: string;
    header: ReactNode;
    render: (row: T) => ReactNode;
    /** The cell holds action buttons (they stay in view on a narrow screen and are laid out in a row) */
    actions?: boolean;
}

interface TableHeaderProps {
    headline: string;
    /** The count shown in the headline in brackets: "Links (124)" */
    count?: number;
    actions?: ReactNode;
}

/** The title of a table with its count, and the actions that go with it on the right, over a thin rule. */
export function TableHeader({ headline, count, actions }: TableHeaderProps): React.ReactElement {
    return (
        <div className="ibexa-table-header">
            <div className="ibexa-table-header__headline">{count === undefined ? headline : `${headline} (${count})`}</div>
            <div className="ibexa-table-header__actions">{actions}</div>
        </div>
    );
}

interface DataTableProps<T> {
    columns: Column<T>[];
    rows: readonly T[];
    rowKey: (row: T) => string;
    /** What the table says when there is nothing to show, and what could be done about it */
    empty: { info: string; action?: ReactNode };
    /** The name of the table for assistive technology (the title above it) */
    label?: string;
}

/** The admin's table: rows alternate in colour, the head is quiet, and an empty table shows its picture and says why. */
export function DataTable<T>({ columns, rows, rowKey, empty, label }: DataTableProps<T>): React.ReactElement {
    const lastIsActions = columns.length > 0 && columns[columns.length - 1].actions === true;

    return (
        <div className="ibexa-scrollable-wrapper">
            <table aria-label={label} className={`ibexa-table table${lastIsActions ? ' ibexa-table--last-column-sticky' : ''}`}>
                <thead hidden={rows.length === 0}>
                    <tr className="ibexa-table__head-row">
                        {columns.map((column) => (
                            <th className="ibexa-table__header-cell" scope="col" key={column.key}>
                                <span className="ibexa-table__header-cell-text-wrapper">{column.header}</span>
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="ibexa-table__body">
                    {rows.length === 0 ? (
                        <tr className="ibexa-table__row">
                            <td className="ibexa-table__empty-table-cell" colSpan={columns.length}>
                                <img className="ibexa-table__empty-table-image" src="/bundles/ibexaadminui/img/ibexa-empty-table.svg" alt="" />
                                <div className="ibexa-table__empty-table-text">
                                    <div className="ibexa-table__empty-table-info-text">{empty.info}</div>
                                    {empty.action !== undefined ? <div className="ibexa-table__empty-table-action-text">{empty.action}</div> : null}
                                </div>
                            </td>
                        </tr>
                    ) : rows.map((row) => (
                        <tr className="ibexa-table__row" key={rowKey(row)}>
                            {columns.map((column) => (
                                <td className={`ibexa-table__cell${column.actions === true ? ' ibexa-table__cell--has-action-btns' : ''}`} key={column.key}>
                                    {column.actions === true ? <div className="ibexa-table__cell-btns-wrapper">{column.render(row)}</div> : column.render(row)}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
