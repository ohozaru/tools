#Something like php print_r for python
tabs_counter = 0

def dump(data, die = False):
    """Prints human-readable information about a variable"""
    print format(data)
    if die:
       exit()

def format(data):
    """Returns variable formatted to human-readable string"""
    global tabs_counter
    if isinstance(data, dict):
        x = '{\n'
        tabs_counter += 1
        for property in data:
            x += ("\t" * tabs_counter) + str(property) + " : " + format(data[property]) + "\n"
        tabs_counter -= 1
        x += ("\t" * tabs_counter) + "}\n"

    elif isinstance(data, (list)):
        x = '[\n'
        tabs_counter += 1
        for property in data:
            x += ("\t" * tabs_counter) + format(property) + ',\n'
        tabs_counter -= 1
        x += ("\t" * tabs_counter) + "]\n"

    elif isinstance(data, (tuple)):
        x = '(\n'
        tabs_counter += 1
        for property in data:
            x += ("\t" * tabs_counter) + format(property) + ',\n'
        tabs_counter -= 1
        x += ("\t" * tabs_counter) + ")\n"

    elif isinstance(data, (str, basestring, int, bool, float)):
        x = str(data)

    elif data is None:
        x = 'Object<None>'

    elif isinstance(data, object):
        try:
            x = 'Object<' + data.__class__.__name__ + '> {\n'
            tabs_counter += 1
            for property in vars(data):
                value = format(getattr(data, property))
                x += ("\t" * tabs_counter) + property + " = " + value + "\n"
            tabs_counter -= 1
            x += ("\t" * tabs_counter) + "}\n"
        except Exception as e:
            tabs_counter -= 1
            x = e.message

    return x
